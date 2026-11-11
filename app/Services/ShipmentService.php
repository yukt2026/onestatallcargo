<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Franchise;
use App\Models\Courier;
use Illuminate\Support\Facades\Log;

class ShipmentService
{
    protected $awbService;
    protected $pricingService;
    protected $serviceabilityService;

    public function __construct(AwbGeneratorService $awbService, PricingService $pricingService, ServiceabilityService $serviceabilityService)
    {
        $this->awbService = $awbService;
        $this->pricingService = $pricingService;
        $this->serviceabilityService = $serviceabilityService;
    }

    /**
     * Calculate route and assign courier/franchise
     */
    public function assignRouting(Shipment $shipment, string $deliveryPincode)
    {
        if ($shipment->is_overridden) {
            // Respect admin override if it is already set
            return;
        }

        $pickupPincode = $shipment->pickup_pincode;
        $routing = $this->serviceabilityService->determineRouting($deliveryPincode, $pickupPincode);

        if ($routing['serviceable']) {
            $shipment->fulfillment_type = $routing['fulfillment_type'];
            
            if ($routing['fulfillment_type'] === 'onestall') {
                $shipment->franchise_id = $routing['district_franchise_id'] ?? $routing['franchise_id'];
                $shipment->state_franchise_id = $routing['state_franchise_id'] ?? null;
                if (!empty($routing['pickup_franchise_id'])) {
                    $shipment->pickup_franchise_id = $routing['pickup_franchise_id'];
                }
                $shipment->provider_id = null;
                $shipment->courier_partner = 'Onestall Franchise'; // Legacy compatibility
            } else {
                $shipment->franchise_id = null;
                $shipment->state_franchise_id = null;
                $shipment->provider_id = $routing['provider_id'];
                
                $provider = Courier::find($routing['provider_id']);
                $shipment->courier_partner = $provider ? $provider->name : 'External Provider';
            }
        } else {
            // Throw exception or set default state if completely unserviceable?
            // Usually you'd throw an exception. We'll set a flag or just leave it.
            $shipment->fulfillment_type = 'unserviceable';
        }
    }

    /**
     * Create a shipment cleanly
     */
    public function createShipment(array $data, int $userId): Shipment
    {
        $shipment = new Shipment();
        $shipment->fill($data);
        $shipment->user_id = $userId;
        
        if (isset($data['vendor_id'])) {
            $shipment->vendor_id = $data['vendor_id'];
        }

        $shipment->awb_number = $this->awbService->generateUniqueAwb();
        $shipment->status = 'Manifested';

        // Auto-fill origin warehouse if omitted
        $user = \App\Models\User::find($userId);
        if ($user) {
            $defaultWh = \App\Models\Warehouse::where('user_id', $userId)->orderByDesc('is_default')->first();
            if (empty(trim($shipment->pickup_name ?? ''))) {
                $shipment->pickup_name = $defaultWh ? ($defaultWh->contact_person ? $defaultWh->contact_person . ' (' . $defaultWh->name . ')' : $defaultWh->name) : ($user->company_name ?: $user->name);
            }
            if (empty(trim($shipment->pickup_phone ?? ''))) {
                $shipment->pickup_phone = $defaultWh ? $defaultWh->phone : $user->phone;
            }
            if (empty(trim($shipment->pickup_address ?? ''))) {
                $shipment->pickup_address = $defaultWh ? $defaultWh->address : ($user->company_address ?: 'Origin Hub');
            }
            if (empty(trim($shipment->pickup_city ?? ''))) {
                $shipment->pickup_city = $defaultWh ? $defaultWh->city : ($user->company_city ?: 'Origin City');
            }
            if (empty(trim($shipment->pickup_state ?? ''))) {
                $shipment->pickup_state = $defaultWh ? $defaultWh->state : $user->company_state;
            }
            if (empty(trim($shipment->pickup_pincode ?? '')) && $defaultWh) {
                $shipment->pickup_pincode = $defaultWh->pincode;
            }
        }

        // Apply Routing Interceptor
        $this->assignRouting($shipment, $data['delivery_pincode']);

        // Apply rates if not provided
        if (!isset($data['shipping_charge']) || $data['shipping_charge'] == 0) {
            $pickupPin = $data['pickup_pincode'] ?? '000000';
            $deliveryPin = $data['delivery_pincode'];
            $weight = $data['weight_kg'];
            $l = $data['length_cm'] ?? 10;
            $b = $data['width_cm'] ?? 10;
            $h = $data['height_cm'] ?? 10;
            $is_cod = ($data['shipment_type'] ?? 'Prepaid') === 'COD';
            $inv_val = $data['invoice_value'] ?? 0;
            
            $rateData = $this->pricingService->calculateRate(
                $shipment->fulfillment_type ?? 'onestall',
                $pickupPin,
                $deliveryPin,
                $weight,
                $l, $b, $h,
                $is_cod,
                $inv_val,
                false, // is_rto
                false, // is_dto
                0,     // qc_params
                $shipment->provider_id
            );
            $shipment->shipping_charge = $rateData['total'];
            $shipment->total_amount = $rateData['total'];
        }

        if (!isset($shipment->total_amount)) {
            $shipment->total_amount = $shipment->shipping_charge;
        }

        $shipment->save();
        
        if ($shipment->fulfillment_type === 'external') {
            \App\Jobs\ProcessExternalShipment::dispatch($shipment);
        }

        return $shipment;
    }
}
