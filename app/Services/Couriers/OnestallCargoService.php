<?php
namespace App\Services\Couriers;

use Illuminate\Support\Str;
use App\Models\Rate;
use App\Models\Courier;

class OnestallCargoService implements CourierInterface
{
    public function __construct(array $credentials = [])
    {
        // Internal service, no API credentials needed
    }

    public function checkServiceability(string $pincode): bool
    {
        // For an in-house courier, we might assume it is serviceable everywhere
        // or check against a local pincodes table if available.
        return true; 
    }

    public function calculateRate(string $pickup_pincode, string $delivery_pincode, float $weight): array
    {
        // Try to find a rate for this specific courier in the rates table
        $courier = Courier::where('name', 'Onestall Cargo')->first();
        $rateRecord = null;
        
        if ($courier) {
            $rateRecord = Rate::where('courier_id', $courier->id)->first();
        }

        // Fallback to a general rate or default if specific rate not set
        if (!$rateRecord) {
            $rateRecord = Rate::whereNull('courier_id')->first();
        }

        if ($rateRecord) {
            $base_rate = $rateRecord->base_rate;
            $additional_weight = max(0, $weight - 0.5);
            $extra_multiplier = ceil($additional_weight / 0.5);
            $total_base = $base_rate + ($extra_multiplier * $rateRecord->additional_weight_rate);
            
            return [
                'status' => 'success',
                'base_rate' => $total_base,
            ];
        }

        // Hard fallback if no rates are configured in DB at all
        $base_rate = 45; 
        $additionalRate = ceil(max(0, $weight - 0.5) / 0.5) * 40;
        
        return [
            'status' => 'success',
            'base_rate' => $base_rate + $additionalRate,
        ];
    }

    public function createShipment(array $shipmentDetails): array
    {
        // Generate an internal AWB number
        $awb = app(\App\Services\AwbGeneratorService::class)->generateUniqueAwb();

        return [
            'status' => 'success',
            'awb_number' => $awb,
            'courier_name' => 'Onestall Cargo',
            'label_url' => null, // Internal labels can be generated locally
            'routing_code' => 'OSC-INTERNAL'
        ];
    }

    public function trackShipment(string $awb): array
    {
        // For internal tracking, the status is whatever is in the local shipments table.
        // The `ShipmentApiController` handles local tracking queries usually.
        return [
            'status' => 'success',
            'tracking_data' => [
                'current_status' => 'In Transit (Internal)',
                'scans' => []
            ]
        ];
    }
}
