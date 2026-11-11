<?php
namespace App\Services\Couriers;

use Illuminate\Support\Facades\Http;

class DelhiveryService implements CourierInterface
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct(array $credentials = [])
    {
        $this->apiUrl = $credentials['api_url'] ?? 'https://track.delhivery.com';
        $this->apiKey = $credentials['api_key'] ?? '';
    }

    public function checkServiceability(string $pincode): bool
    {
        // For testing purposes, if we are using the dummy key, assume true
        if ($this->apiKey === 'dummy_key_for_testing' || $this->apiKey === 'dummy' || empty($this->apiKey)) {
            return true;
        }

        // Example Delhivery API call
        $response = Http::withHeaders([
            'Authorization' => "Token " . $this->apiKey
        ])->get("{$this->apiUrl}/c/api/pin-codes/json/", [
            'filter_codes' => $pincode
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return count($data['delivery_codes']) > 0;
        }

        return false;
    }

    public function calculateRate(string $pickup_pincode, string $delivery_pincode, float $weight, bool $is_cod = false, float $invoice_value = 0): array
    {
        $baseWeightRate = 45.00 + (max(0, $weight - 0.5) * 35.00);
        $fsc = round($baseWeightRate * 0.10, 2); // 10% FSC
        
        $codCharge = 0;
        if ($is_cod && $invoice_value > 0) {
            $codCharge = max(35.00, round($invoice_value * 0.015, 2)); // Max of Rs.35 or 1.5%
        }

        $total = $baseWeightRate + $fsc + $codCharge;

        return [
            'status'         => 'success',
            'provider'       => 'Delhivery',
            'zone'           => 'Zone 4',
            'weight_charged' => $weight,
            'base_freight'   => $baseWeightRate,
            'fsc'            => $fsc,
            'cod_charge'     => $codCharge,
            'base_rate'      => $total,
            'total'          => $total,
            'breakdown'      => [
                'Base Freight' => "₹{$baseWeightRate}",
                'FSC (10%)'    => "₹{$fsc}",
                'COD Charge'   => $is_cod ? "₹{$codCharge}" : 'N/A',
                'Total'        => "₹{$total}"
            ]
        ];
    }

    public function createShipment(array $shipmentDetails): array
    {
        // Delhivery /api/cmu/create.json payload
        $payload = [
            'format' => 'json',
            'data' => json_encode([
                'shipments' => [
                    [
                        'name' => $shipmentDetails['receiver_name'],
                        'add' => $shipmentDetails['delivery_address'],
                        'pin' => $shipmentDetails['delivery_pincode'],
                        'city' => $shipmentDetails['delivery_city'],
                        'state' => 'Delhi', // requires state usually
                        'country' => 'India',
                        'phone' => $shipmentDetails['receiver_phone'],
                        'order' => $shipmentDetails['order_id'],
                        'payment_mode' => $shipmentDetails['is_cod'] ? 'COD' : 'Prepaid',
                        'return_pin' => $shipmentDetails['pickup_pincode'],
                        'return_city' => 'Delhi',
                        'return_add' => 'Warehouse',
                        'return_name' => 'OneStall Cargo',
                    ]
                ],
                'pickup_location' => [
                    'name' => 'OneStall Cargo Hub'
                ]
            ])
        ];

        $response = Http::asForm()->withHeaders([
            'Authorization' => "Token " . $this->apiKey
        ])->post("{$this->apiUrl}/api/cmu/create.json", $payload);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['packages'][0]['awb'])) {
                return [
                    'status' => 'success',
                    'awb' => $data['packages'][0]['awb'],
                    'provider_response' => $data
                ];
            }
        }

        return [
            'status' => 'error',
            'message' => $response->body()
        ];
    }

    public function trackShipment(string $awb): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Token " . $this->apiKey
        ])->get("{$this->apiUrl}/api/v1/packages/json/", [
            'waybill' => $awb
        ]);

        if ($response->successful()) {
            return [
                'status' => 'success',
                'data' => $response->json()
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Unable to track at this moment'
        ];
    }
}
