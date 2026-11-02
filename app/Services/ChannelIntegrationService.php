<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ChannelIntegrationService
{
    /**
     * Connect or update a WooCommerce store.
     */
    public function connectWooCommerce(User $user, array $data): Channel
    {
        $storeUrl = rtrim(trim($data['store_url']), '/');
        if (!preg_match('/^https?:\/\//i', $storeUrl)) {
            $storeUrl = 'https://' . $storeUrl;
        }

        $consumerKey = trim($data['consumer_key'] ?? '');
        $consumerSecret = trim($data['consumer_secret'] ?? '');
        $name = trim($data['name'] ?? 'WooCommerce Store');

        // Verify credentials with a quick API ping
        $isConnected = false;
        try {
            $response = Http::timeout(5)
                ->withBasicAuth($consumerKey, $consumerSecret)
                ->get("{$storeUrl}/wp-json/wc/v3/system_status");

            if ($response->successful()) {
                $isConnected = true;
            } else {
                // Try orders endpoint as alternate test
                $orderTest = Http::timeout(5)
                    ->withBasicAuth($consumerKey, $consumerSecret)
                    ->get("{$storeUrl}/wp-json/wc/v3/orders", ['per_page' => 1]);
                if ($orderTest->successful()) {
                    $isConnected = true;
                }
            }
        } catch (\Throwable $e) {
            Log::info("WooCommerce ping check failed (saving anyway): " . $e->getMessage());
        }

        return Channel::updateOrCreate(
            [
                'user_id' => $user->id,
                'platform' => 'woocommerce',
                'store_url' => $storeUrl,
            ],
            [
                'name' => $name,
                'consumer_key' => $consumerKey,
                'consumer_secret' => $consumerSecret,
                'webhook_secret' => Str::random(32),
                'is_active' => true,
                'settings' => [
                    'verified' => $isConnected,
                    'auto_sync' => true,
                    'import_statuses' => ['processing', 'pending'],
                ],
            ]
        );
    }

    /**
     * Connect or update a Shopify store.
     */
    public function connectShopify(User $user, array $data): Channel
    {
        $storeUrl = rtrim(trim($data['store_url']), '/');
        $storeUrl = preg_replace('/^https?:\/\//i', '', $storeUrl);

        $accessToken = trim($data['access_token'] ?? '');
        $name = trim($data['name'] ?? 'Shopify Store');

        $isConnected = false;
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Shopify-Access-Token' => $accessToken])
                ->get("https://{$storeUrl}/admin/api/2024-01/shop.json");

            if ($response->successful()) {
                $isConnected = true;
                $shopData = $response->json('shop');
                if (!empty($shopData['name'])) {
                    $name = $shopData['name'];
                }
            }
        } catch (\Throwable $e) {
            Log::info("Shopify ping check failed (saving anyway): " . $e->getMessage());
        }

        return Channel::updateOrCreate(
            [
                'user_id' => $user->id,
                'platform' => 'shopify',
                'store_url' => $storeUrl,
            ],
            [
                'name' => $name,
                'consumer_secret' => $accessToken,
                'webhook_secret' => Str::random(32),
                'is_active' => true,
                'settings' => [
                    'verified' => $isConnected,
                    'auto_sync' => true,
                ],
            ]
        );
    }

    /**
     * Connect a custom store / webhook channel.
     */
    public function connectCustom(User $user, array $data): Channel
    {
        $storeUrl = rtrim(trim($data['store_url'] ?? 'https://custom-store.local'), '/');
        $name = trim($data['name'] ?? 'Custom Store');

        return Channel::create([
            'user_id' => $user->id,
            'platform' => 'custom',
            'name' => $name,
            'store_url' => $storeUrl,
            'webhook_secret' => Str::random(32),
            'is_active' => true,
            'settings' => ['api_mode' => 'webhook'],
        ]);
    }

    /**
     * Sync orders from a connected channel.
     */
    public function syncOrders(Channel $channel): array
    {
        if ($channel->platform === 'woocommerce') {
            return $this->syncWooCommerceOrders($channel);
        }

        if ($channel->platform === 'shopify') {
            return $this->syncShopifyOrders($channel);
        }

        return [
            'success' => false,
            'message' => 'Manual pull sync is not supported for custom webhooks. Use the Webhook URL to push orders in real time.',
            'count' => 0,
        ];
    }

    /**
     * Sync orders from WooCommerce.
     */
    protected function syncWooCommerceOrders(Channel $channel): array
    {
        $storeUrl = $channel->store_url;
        $consumerKey = $channel->consumer_key;
        $consumerSecret = $channel->consumer_secret;

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($consumerKey, $consumerSecret)
                ->get("{$storeUrl}/wp-json/wc/v3/orders", [
                    'status' => 'processing,pending',
                    'per_page' => 25,
                ]);

            if (!$response->successful()) {
                throw new Exception("WooCommerce API returned HTTP {$response->status()}: " . $response->body());
            }

            $orders = $response->json();
            if (!is_array($orders)) {
                return ['success' => true, 'count' => 0, 'message' => 'No new processing orders found in WooCommerce store.'];
            }

            $imported = 0;
            foreach ($orders as $order) {
                if ($this->importWooCommerceOrder($channel, $order)) {
                    $imported++;
                }
            }

            $channel->last_sync_at = now();
            $channel->synced_orders_count += $imported;
            $channel->save();

            return [
                'success' => true,
                'count' => $imported,
                'message' => "Successfully synced {$imported} new order(s) from {$channel->name}.",
            ];
        } catch (\Throwable $e) {
            Log::error("WooCommerce sync failed for channel {$channel->id}: " . $e->getMessage());
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Could not sync from WooCommerce: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync orders from Shopify.
     */
    protected function syncShopifyOrders(Channel $channel): array
    {
        $storeUrl = $channel->store_url;
        $accessToken = $channel->consumer_secret;

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Shopify-Access-Token' => $accessToken])
                ->get("https://{$storeUrl}/admin/api/2024-01/orders.json", [
                    'status' => 'open',
                    'fulfillment_status' => 'unfulfilled',
                    'limit' => 25,
                ]);

            if (!$response->successful()) {
                throw new Exception("Shopify API returned HTTP {$response->status()}: " . $response->body());
            }

            $data = $response->json();
            $orders = $data['orders'] ?? [];

            $imported = 0;
            foreach ($orders as $order) {
                if ($this->importShopifyOrder($channel, $order)) {
                    $imported++;
                }
            }

            $channel->last_sync_at = now();
            $channel->synced_orders_count += $imported;
            $channel->save();

            return [
                'success' => true,
                'count' => $imported,
                'message' => "Successfully synced {$imported} new order(s) from {$channel->name}.",
            ];
        } catch (\Throwable $e) {
            Log::error("Shopify sync failed for channel {$channel->id}: " . $e->getMessage());
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Could not sync from Shopify: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Import a single WooCommerce order into shipments.
     */
    public function importWooCommerceOrder(Channel $channel, array $order): bool
    {
        $orderId = (string)($order['id'] ?? '');
        if (empty($orderId)) {
            return false;
        }

        $formattedOrderId = 'WC-' . $orderId;

        // Prevent duplicate import
        $exists = Shipment::where('user_id', $channel->user_id)
            ->where(function ($q) use ($formattedOrderId, $orderId) {
                $q->where('order_id', $formattedOrderId)
                  ->orWhere('external_shipment_id', $orderId);
            })
            ->exists();

        if ($exists) {
            return false;
        }

        $shipping = $order['shipping'] ?? [];
        $billing = $order['billing'] ?? [];

        $receiverName = trim(($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? ''));
        if (empty($receiverName)) {
            $receiverName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')) ?: 'Store Customer';
        }

        $receiverPhone = $billing['phone'] ?? $shipping['phone'] ?? '9999999999';
        $receiverEmail = $billing['email'] ?? null;

        $address1 = $shipping['address_1'] ?? $billing['address_1'] ?? 'Customer Address';
        $address2 = $shipping['address_2'] ?? $billing['address_2'] ?? '';
        $deliveryAddress = trim("{$address1} {$address2}");

        $deliveryCity = $shipping['city'] ?? $billing['city'] ?? 'City';
        $deliveryPincode = preg_replace('/\D/', '', $shipping['postcode'] ?? $billing['postcode'] ?? '000000');
        $deliveryState = $shipping['state'] ?? $billing['state'] ?? '';

        // Payment Mode
        $paymentMethod = strtolower($order['payment_method'] ?? '');
        $isCod = in_array($paymentMethod, ['cod', 'cash_on_delivery']);
        $totalAmount = (float)($order['total'] ?? 0);

        // Products info
        $items = $order['line_items'] ?? [];
        $itemNames = [];
        $totalQty = 0;
        foreach ($items as $item) {
            $itemNames[] = $item['name'] ?? 'Item';
            $totalQty += (int)($item['quantity'] ?? 1);
        }
        $productName = !empty($itemNames) ? Str::limit(implode(', ', $itemNames), 100) : 'E-commerce Item';

        // Pickup Warehouse
        $warehouse = Warehouse::where('user_id', $channel->user_id)
            ->orderByDesc('is_default')
            ->first();

        $pickupAddress = $warehouse->address ?? 'Origin Hub / Warehouse';
        $pickupCity = $warehouse->city ?? 'Origin City';
        $pickupState = $warehouse->state ?? null;
        $pickupPincode = $warehouse->pincode ?? '110001';
        $pickupName = $warehouse ? ($warehouse->contact_person ? $warehouse->contact_person . ' (' . $warehouse->name . ')' : $warehouse->name) : null;
        $pickupPhone = $warehouse->phone ?? null;

        // Generate AWB
        $awbNumber = app(\App\Services\AwbGeneratorService::class)->generateUniqueAwb();

        $shipment = Shipment::create([
            'user_id' => $channel->user_id,
            'awb_number' => $awbNumber,
            'order_id' => $formattedOrderId,
            'external_shipment_id' => $orderId,
            'shipment_type' => 'B2C',
            'receiver_name' => $receiverName,
            'receiver_phone' => $receiverPhone,
            'receiver_email' => $receiverEmail,
            'delivery_address' => $deliveryAddress,
            'delivery_city' => $deliveryCity,
            'delivery_pincode' => $deliveryPincode,
            'delivery_state' => $deliveryState,
            'pickup_name' => $pickupName,
            'pickup_phone' => $pickupPhone,
            'pickup_address' => $pickupAddress,
            'pickup_city' => $pickupCity,
            'pickup_state' => $pickupState,
            'pickup_pincode' => $pickupPincode,
            'weight_kg' => 0.5,
            'invoice_value' => $totalAmount,
            'is_cod' => $isCod,
            'cod_amount' => $isCod ? $totalAmount : 0,
            'product_name' => $productName,
            'product_qty' => $totalQty ?: 1,
            'status' => 'New',
            'total_amount' => 0,
            'shipping_charge' => 0,
            'courier_partner' => 'OneStall Cargo',
        ]);

        return true;
    }

    /**
     * Import a single Shopify order into shipments.
     */
    public function importShopifyOrder(Channel $channel, array $order): bool
    {
        $orderId = (string)($order['id'] ?? '');
        if (empty($orderId)) {
            return false;
        }

        $orderNumber = $order['order_number'] ?? $orderId;
        $formattedOrderId = 'SH-' . $orderNumber;

        $exists = Shipment::where('user_id', $channel->user_id)
            ->where(function ($q) use ($formattedOrderId, $orderId) {
                $q->where('order_id', $formattedOrderId)
                  ->orWhere('external_shipment_id', $orderId);
            })
            ->exists();

        if ($exists) {
            return false;
        }

        $shipping = $order['shipping_address'] ?? $order['billing_address'] ?? [];
        $receiverName = $shipping['name'] ?? trim(($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '')) ?: 'Shopify Customer';
        $receiverPhone = $shipping['phone'] ?? $order['phone'] ?? '9999999999';
        $receiverEmail = $order['email'] ?? null;

        $deliveryAddress = trim(($shipping['address1'] ?? '') . ' ' . ($shipping['address2'] ?? '')) ?: 'Customer Address';
        $deliveryCity = $shipping['city'] ?? 'City';
        $deliveryPincode = preg_replace('/\D/', '', $shipping['zip'] ?? '000000');
        $deliveryState = $shipping['province'] ?? '';

        $gateway = strtolower($order['gateway'] ?? '');
        $financialStatus = strtolower($order['financial_status'] ?? '');
        $isCod = ($gateway === 'cash_on_delivery' || $gateway === 'cod' || $financialStatus === 'pending');
        $totalAmount = (float)($order['total_price'] ?? 0);

        $lineItems = $order['line_items'] ?? [];
        $itemNames = [];
        $totalQty = 0;
        foreach ($lineItems as $item) {
            $itemNames[] = $item['title'] ?? 'Item';
            $totalQty += (int)($item['quantity'] ?? 1);
        }
        $productName = !empty($itemNames) ? Str::limit(implode(', ', $itemNames), 100) : 'Shopify Item';

        $warehouse = Warehouse::where('user_id', $channel->user_id)
            ->orderByDesc('is_default')
            ->first();

        $pickupAddress = $warehouse->address ?? 'Origin Hub / Warehouse';
        $pickupCity = $warehouse->city ?? 'Origin City';
        $pickupState = $warehouse->state ?? null;
        $pickupPincode = $warehouse->pincode ?? '110001';
        $pickupName = $warehouse ? ($warehouse->contact_person ? $warehouse->contact_person . ' (' . $warehouse->name . ')' : $warehouse->name) : null;
        $pickupPhone = $warehouse->phone ?? null;

        $awbNumber = app(\App\Services\AwbGeneratorService::class)->generateUniqueAwb();

        $shipment = Shipment::create([
            'user_id' => $channel->user_id,
            'awb_number' => $awbNumber,
            'order_id' => $formattedOrderId,
            'external_shipment_id' => $orderId,
            'shipment_type' => 'B2C',
            'receiver_name' => $receiverName,
            'receiver_phone' => $receiverPhone,
            'receiver_email' => $receiverEmail,
            'delivery_address' => $deliveryAddress,
            'delivery_city' => $deliveryCity,
            'delivery_pincode' => $deliveryPincode,
            'delivery_state' => $deliveryState,
            'pickup_name' => $pickupName,
            'pickup_phone' => $pickupPhone,
            'pickup_address' => $pickupAddress,
            'pickup_city' => $pickupCity,
            'pickup_state' => $pickupState,
            'pickup_pincode' => $pickupPincode,
            'weight_kg' => 0.5,
            'invoice_value' => $totalAmount,
            'is_cod' => $isCod,
            'cod_amount' => $isCod ? $totalAmount : 0,
            'product_name' => $productName,
            'product_qty' => $totalQty ?: 1,
            'status' => 'New',
            'total_amount' => 0,
            'shipping_charge' => 0,
            'courier_partner' => 'OneStall Cargo',
        ]);

        return true;
    }

    /**
     * Handle incoming real-time webhook order.
     */
    public function handleWebhook(string $platform, int $userId, array $payload): array
    {
        $channel = Channel::where('user_id', $userId)
            ->where('platform', $platform)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            // Auto-create a channel for this user if payload arrives
            $channel = Channel::create([
                'user_id' => $userId,
                'platform' => $platform,
                'name' => ucfirst($platform) . ' Store',
                'store_url' => $payload['store_url'] ?? 'webhook',
                'is_active' => true,
            ]);
        }

        $imported = false;
        if ($platform === 'woocommerce') {
            $imported = $this->importWooCommerceOrder($channel, $payload);
        } elseif ($platform === 'shopify') {
            $imported = $this->importShopifyOrder($channel, $payload);
        } else {
            // Generic custom payload
            $imported = $this->importGenericOrder($channel, $payload);
        }

        if ($imported) {
            $channel->last_sync_at = now();
            $channel->increment('synced_orders_count');
        }

        return [
            'success' => true,
            'imported' => $imported,
            'message' => $imported ? 'Order imported successfully.' : 'Order already exists or ignored.',
        ];
    }

    /**
     * Import a generic custom webhook order.
     */
    protected function importGenericOrder(Channel $channel, array $payload): bool
    {
        $orderId = (string)($payload['order_id'] ?? $payload['id'] ?? Str::random(8));
        $formattedOrderId = 'CUSTOM-' . $orderId;

        $exists = Shipment::where('user_id', $channel->user_id)
            ->where('order_id', $formattedOrderId)
            ->exists();

        if ($exists) {
            return false;
        }

        $warehouse = Warehouse::where('user_id', $channel->user_id)->orderByDesc('is_default')->first();
        $pickupName = $payload['pickup_name'] ?? ($warehouse ? ($warehouse->contact_person ? $warehouse->contact_person . ' (' . $warehouse->name . ')' : $warehouse->name) : null);
        $pickupPhone = $payload['pickup_phone'] ?? ($warehouse->phone ?? null);
        $pickupAddress = $payload['pickup_address'] ?? ($warehouse->address ?? 'Origin Hub');
        $pickupCity = $payload['pickup_city'] ?? ($warehouse->city ?? 'Origin City');
        $pickupState = $payload['pickup_state'] ?? ($warehouse->state ?? null);
        $pickupPincode = $payload['pickup_pincode'] ?? ($warehouse->pincode ?? '110001');

        Shipment::create([
            'user_id' => $channel->user_id,
            'awb_number' => app(\App\Services\AwbGeneratorService::class)->generateUniqueAwb(),
            'order_id' => $formattedOrderId,
            'external_shipment_id' => $orderId,
            'shipment_type' => 'B2C',
            'receiver_name' => $payload['receiver_name'] ?? $payload['name'] ?? 'Customer',
            'receiver_phone' => $payload['receiver_phone'] ?? $payload['phone'] ?? '9999999999',
            'receiver_email' => $payload['receiver_email'] ?? $payload['email'] ?? null,
            'delivery_address' => $payload['delivery_address'] ?? $payload['address'] ?? 'Address',
            'delivery_city' => $payload['delivery_city'] ?? $payload['city'] ?? 'City',
            'delivery_pincode' => $payload['delivery_pincode'] ?? $payload['pincode'] ?? '110001',
            'delivery_state' => $payload['delivery_state'] ?? $payload['state'] ?? '',
            'pickup_name' => $pickupName,
            'pickup_phone' => $pickupPhone,
            'pickup_address' => $pickupAddress,
            'pickup_city' => $pickupCity,
            'pickup_state' => $pickupState,
            'pickup_pincode' => $pickupPincode,
            'weight_kg' => (float)($payload['weight_kg'] ?? 0.5),
            'invoice_value' => (float)($payload['invoice_value'] ?? $payload['total'] ?? 0),
            'is_cod' => (bool)($payload['is_cod'] ?? false),
            'cod_amount' => !empty($payload['is_cod']) ? (float)($payload['invoice_value'] ?? 0) : 0,
            'product_name' => $payload['product_name'] ?? 'Package Item',
            'product_qty' => (int)($payload['product_qty'] ?? 1),
            'status' => 'New',
            'total_amount' => 0,
            'shipping_charge' => 0,
            'courier_partner' => 'OneStall Cargo',
        ]);

        return true;
    }
}
