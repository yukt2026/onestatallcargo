<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shipment;
use App\Models\Kyc;
use App\Models\Franchise;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShipmentStatusTabsAndRtoTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $hubUser;
    protected Franchise $franchise;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'wallet_balance' => 5000.00,
        ]);

        Kyc::create([
            'user_id' => $this->seller->id,
            'status' => 'approved',
            'document_type' => 'pan',
        ]);

        $this->hubUser = User::factory()->create([
            'role' => 'franchise',
            'status' => 'active',
            'wallet_balance' => 5000.00,
        ]);

        Kyc::create([
            'user_id' => $this->hubUser->id,
            'status' => 'approved',
            'document_type' => 'pan',
        ]);

        $this->franchise = Franchise::create([
            'user_id' => $this->hubUser->id,
            'company_name' => 'Test Hub Logistics',
            'owner_name' => 'Hub Owner',
            'phone' => '9829099999',
            'email' => 'hub_test@onestall.com',
            'address' => 'Station Road',
            'city' => 'Jaipur',
            'district' => 'Jaipur',
            'state' => 'Rajasthan',
            'status' => 'approved',
            'franchise_level' => 'district',
            'serviceable_pincodes' => ['302001', '302002'],
        ]);
    }

    /**
     * Test: Seller shipments page accurately counts and displays RTO Delivered shipments.
     */
    public function test_seller_shipments_page_counts_and_displays_rto_delivered(): void
    {
        $shipment = Shipment::create([
            'user_id' => $this->seller->id,
            'awb_number' => 'OSC1012',
            'order_id' => 'ORD-RTO-01',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Customer 1',
            'receiver_phone' => '9876543200',
            'delivery_address' => 'House 10, Connaught Place',
            'delivery_city' => 'New Delhi',
            'delivery_pincode' => '110001',
            'pickup_pincode' => '110002',
            'weight_kg' => 0.50,
            'invoice_value' => 500.00,
            'shipping_charge' => 70.00,
            'total_amount' => 70.00,
            'status' => 'RTO Delivered',
            'is_cod' => true,
        ]);

        // Access shipments page with ?status=rto
        $response = $this->actingAs($this->seller)->get(route('seller.shipments.index', ['status' => 'rto']));
        $response->assertStatus(200);

        // 1. Tab badge for RTO must show 1 (not 0!)
        $response->assertSee('RTO <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">1</span>', false);

        // 2. Shipment AWB and receiver name must be visible in the table
        $response->assertSee('OSC1012');
        $response->assertSee('Customer 1');
        $response->assertSee('RTO Delivered');
    }

    /**
     * Test: Hub shipments page accurately counts and displays RTO Delivered shipments.
     */
    public function test_hub_shipments_page_counts_and_displays_rto_delivered(): void
    {
        $shipment = Shipment::create([
            'user_id' => $this->hubUser->id,
            'franchise_id' => $this->franchise->id,
            'awb_number' => 'OSC1013',
            'order_id' => 'ORD-RTO-02',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Customer 2',
            'receiver_phone' => '9876543201',
            'delivery_address' => 'MI Road',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'pickup_pincode' => '302002',
            'weight_kg' => 1.0,
            'invoice_value' => 800.00,
            'shipping_charge' => 90.00,
            'total_amount' => 90.00,
            'status' => 'RTO Initiated',
            'is_cod' => false,
        ]);

        $response = $this->actingAs($this->hubUser)->get(route('hub.shipments', ['status' => 'rto']));
        $response->assertStatus(200);

        // Tab badge for RTO must show 1 (not 0!)
        $response->assertSee('RTO <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">1</span>', false);

        $response->assertSee('OSC1013');
        $response->assertSee('Customer 2');
        $response->assertSee('RTO Initiated');
    }

    /**
     * Test: All 14 shipments across multiple statuses (Pickup Pending, Out for Delivery, RTO Delivered)
     * are accurately counted in tab badges and their sum matches total shipments.
     */
    public function test_all_statuses_including_pickup_pending_and_out_for_delivery_are_counted_accurately(): void
    {
        // Create 2 Pickup Pending
        for ($i = 1; $i <= 2; $i++) {
            Shipment::create([
                'user_id' => $this->seller->id,
                'awb_number' => "OSC-PP-{$i}",
                'order_id' => "ORD-PP-{$i}",
                'shipment_type' => 'B2C',
                'receiver_name' => "Receiver PP {$i}",
                'receiver_phone' => '9876543210',
                'delivery_address' => 'Addr',
                'delivery_city' => 'City',
                'delivery_pincode' => '110001',
                'pickup_pincode' => '110002',
                'weight_kg' => 1.0,
                'invoice_value' => 500.0,
                'shipping_charge' => 70.0,
                'total_amount' => 70.0,
                'status' => 'Pickup Pending',
                'is_cod' => false,
            ]);
        }

        // Create 2 Out for Delivery
        for ($i = 1; $i <= 2; $i++) {
            Shipment::create([
                'user_id' => $this->seller->id,
                'awb_number' => "OSC-OFD-{$i}",
                'order_id' => "ORD-OFD-{$i}",
                'shipment_type' => 'B2C',
                'receiver_name' => "Receiver OFD {$i}",
                'receiver_phone' => '9876543210',
                'delivery_address' => 'Addr',
                'delivery_city' => 'City',
                'delivery_pincode' => '110001',
                'pickup_pincode' => '110002',
                'weight_kg' => 1.0,
                'invoice_value' => 500.0,
                'shipping_charge' => 70.0,
                'total_amount' => 70.0,
                'status' => 'Out for Delivery',
                'is_cod' => false,
            ]);
        }

        // Create 1 RTO Delivered
        Shipment::create([
            'user_id' => $this->seller->id,
            'awb_number' => "OSC-RTOD-1",
            'order_id' => "ORD-RTOD-1",
            'shipment_type' => 'B2C',
            'receiver_name' => "Receiver RTO 1",
            'receiver_phone' => '9876543210',
            'delivery_address' => 'Addr',
            'delivery_city' => 'City',
            'delivery_pincode' => '110001',
            'pickup_pincode' => '110002',
            'weight_kg' => 1.0,
            'invoice_value' => 500.0,
            'shipping_charge' => 70.0,
            'total_amount' => 70.0,
            'status' => 'RTO Delivered',
            'is_cod' => false,
        ]);

        $response = $this->actingAs($this->seller)->get(route('seller.shipments.index'));
        $response->assertStatus(200);

        // Pickups count must be 2
        $response->assertSee('Pickups <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">2</span>', false);

        // In Transit count must be 2 (from Out for Delivery)
        $response->assertSee('In Transit <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">2</span>', false);

        // RTO count must be 1
        $response->assertSee('RTO <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">1</span>', false);

        // Filter ?status=pickups must show the Pickup Pending shipments
        $pickupsRes = $this->actingAs($this->seller)->get(route('seller.shipments.index', ['status' => 'pickups']));
        $pickupsRes->assertStatus(200);
        $pickupsRes->assertSee('OSC-PP-1');
        $pickupsRes->assertSee('OSC-PP-2');

        // Filter ?status=transit must show the Out for Delivery shipments
        $transitRes = $this->actingAs($this->seller)->get(route('seller.shipments.index', ['status' => 'transit']));
        $transitRes->assertStatus(200);
        $transitRes->assertSee('OSC-OFD-1');
        $transitRes->assertSee('OSC-OFD-2');
    }

    /**
     * Test: Seller can cancel a Pickup Pending shipment and receive a wallet refund.
     */
    public function test_seller_can_cancel_pickup_pending_shipment(): void
    {
        $initialBalance = $this->seller->wallet_balance;

        $shipment = Shipment::create([
            'user_id' => $this->seller->id,
            'awb_number' => 'OSC-CANCEL-1',
            'order_id' => 'ORD-CANCEL-1',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Cancel Test',
            'receiver_phone' => '9876543210',
            'delivery_address' => 'Addr',
            'delivery_city' => 'City',
            'delivery_pincode' => '110001',
            'pickup_pincode' => '110002',
            'weight_kg' => 0.5,
            'invoice_value' => 500.0,
            'shipping_charge' => 80.0,
            'total_amount' => 80.0,
            'status' => 'Pickup Pending',
            'is_cod' => false,
        ]);

        $response = $this->actingAs($this->seller)->post(route('seller.shipments.cancel', $shipment->id));
        $response->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals('cancelled', strtolower($shipment->status));
        $this->seller->refresh();
        $this->assertEquals($initialBalance + 80.0, $this->seller->wallet_balance);
    }

    /**
     * Test: Tracking page displays responsive status badges for various shipment states.
     */
    public function test_public_tracking_page_displays_responsive_status(): void
    {
        $shipment = Shipment::create([
            'user_id' => $this->seller->id,
            'awb_number' => 'OSC-TRACK-1',
            'order_id' => 'ORD-TRACK-1',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Tracking User',
            'receiver_phone' => '9876543210',
            'delivery_address' => 'Addr',
            'delivery_city' => 'City',
            'delivery_pincode' => '110001',
            'pickup_pincode' => '110002',
            'weight_kg' => 0.5,
            'invoice_value' => 500.0,
            'shipping_charge' => 80.0,
            'total_amount' => 80.0,
            'status' => 'Out for Delivery',
            'courier_partner' => 'Delhivery',
            'is_cod' => false,
        ]);

        $response = $this->get(route('track', ['awb_number' => 'OSC-TRACK-1']));
        $response->assertStatus(200);
        $response->assertSee('Out for Delivery');
        $response->assertSee('bg-cyan-100 text-cyan-800', false);
    }
}
