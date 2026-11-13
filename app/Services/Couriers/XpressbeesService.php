<?php

namespace App\Services\Couriers;

use App\Models\RateCard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * XpressbeesService
 *
 * Margin Applied  : +20% on all base freight rates
 *
 * Zone Definitions (Xpressbees):
 *   Zone1 (Local)      - Same city / same municipal limits
 *   Zone2 (Regional)   - Cluster pin codes within origin region
 *   Zone3 (Metros)     - BOM, DEL, HYD, BLR, MAA, CCU (excl. local/origin metro)
 *   Zone4 (Rest India) - All others excl. NE / J&K / Kerala
 *   Zone5 (NE/J&K/KL)  - Northeast, J&K, and Kerala pin codes
 *   Zone6 (Special)    - Port Blair / Andaman & Nicobar (not quoted)
 */
class XpressbeesService implements CourierInterface
{
    protected array $credentials;

    // ─── MARGIN (20%) ────────────────────────────────────────────────────────
      // 20% added to base freight

    // ─── Zone map: rough pincode prefix → zone index (0-based) ──────────────
    private const ZONE_PREFIXES = [
        // Zone5 – NE / J&K / Kerala
        '79' => 4, '78' => 4, '77' => 4, '67' => 4, '68' => 4, '69' => 4,
        '18' => 4, '19' => 4,
        // Zone3 – Metros
        '40' => 2, '41' => 2,
        '11' => 2,
        '50' => 2,
        '56' => 2,
        '60' => 2,
        '70' => 2,
    ];

    public function __construct(array $credentials = [])
    {
        $configCreds = config('services.xpressbees') ?? [];
        $this->credentials = array_merge($configCreds, array_filter($credentials));
    }



    public function calculateRate(
        string $pickup_pincode,
        string $delivery_pincode,
        float  $weight = 0.5,
        bool   $is_cod = false,
        float  $invoice_value = 0
    ): array {
        $zoneIndex = $this->detectZone($pickup_pincode, $delivery_pincode);
        $zoneName = 'Zone ' . ($zoneIndex + 1);

        // Fetch dynamic RateCard for Xpressbees
        $courier = \App\Models\Courier::where('name', 'LIKE', '%Xpressbees%')->first();
        $rateCard = RateCard::with('zones')
            ->where('is_active', true)
            ->where(function($q) use ($courier) {
                if ($courier) {
                    $q->where('courier_id', $courier->id);
                } else {
                    $q->where('courier_id', 2);
                }
            })
            ->latest()
            ->first();
        
        if (!$rateCard) {
            // Standard fallback rate for Xpressbees (Zone based)
            $zoneMultipliers = [0 => 38.00, 1 => 44.00, 2 => 52.00, 3 => 65.00, 4 => 85.00];
            $baseFreight = ($zoneMultipliers[$zoneIndex] ?? 55.00) + (max(0, $weight - 0.5) * 35.00);
            $fsc = round($baseFreight * 0.10, 2);
            $subtotal = round($baseFreight + $fsc, 2);
            $codCharge = $is_cod ? max(35.00, round($invoice_value * 0.015, 2)) : 0;
            $total = round($subtotal + $codCharge, 2);

            return [
                'status'         => 'success',
                'provider'       => 'Xpressbees',
                'zone'           => $zoneName,
                'weight_charged' => $weight,
                'base_freight'   => $baseFreight,
                'fsc'            => $fsc,
                'margin_pct'     => '0%',
                'subtotal'       => $subtotal,
                'cod_charge'     => $codCharge,
                'cod_formula'    => "Max(₹35, 1.5% of invoice value)",
                'base_rate'      => $total,
                'total'          => $total,
                'breakdown'      => [
                    'Base Freight' => "₹{$baseFreight}",
                    'FSC (10%)'    => "₹{$fsc}",
                    'COD Charge'   => $is_cod ? "₹{$codCharge}" : 'N/A',
                    'Grand Total'  => "₹{$total}",
                ],
            ];
        }

        $zoneRate = $rateCard->zones->firstWhere('zone_name', $zoneName);
        if (!$zoneRate) {
            return ['status' => 'error', 'message' => "Pricing not available for $zoneName"];
        }

        $rawBaseFreight = $this->calculateFreight($weight, $zoneRate);

        // FSC (dynamic from rate card)
        $fsc_percent = $rateCard->fsc_percent / 100;
        $rawFsc = round($rawBaseFreight * $fsc_percent, 2);

        // Apply Margin separately so PricingService can add them without double counting
        $baseFreight = $rawBaseFreight;
        $fsc = $rawFsc;

        // Sub-total before COD
        $subtotal = round($baseFreight + $fsc, 2);

        // COD surcharge (dynamic from rate card)
        $codCharge = $rateCard->calculateCodCharge($invoice_value, $is_cod);

        $total = round($subtotal + $codCharge, 2);

        return [
            'status'         => 'success',
            'provider'       => 'Xpressbees',
            'zone'           => $zoneName,
            'weight_charged' => $weight,
            'base_freight'   => $baseFreight, // Now includes 20% margin
            'fsc'            => $fsc,         // Now includes 20% margin
            'margin_pct'     => '20%',
            'subtotal'       => $subtotal,
            'cod_charge'     => $codCharge,
            'cod_formula'    => "Max(₹{$rateCard->cod_min_charge}, {$rateCard->cod_percent}% of invoice value)",
            'base_rate'      => $total,
            'total'          => $total,
            'breakdown'      => [
                'Raw Base Freight' => "₹{$rawBaseFreight}",
                'Raw FSC'          => "₹{$rawFsc}",
                'Margin (20%)'     => 'Applied to Base & FSC',
                'COD Charge'       => $is_cod ? "₹{$codCharge}" : 'N/A',
                'Grand Total'      => "₹{$total}",
            ],
        ];
    }

    /**
     * Generate or Retrieve Cached Xpressbees Auth Token
     */
    private function getToken(): string
    {
        $cacheKey = 'xb_token_' . md5($this->credentials['username'] ?? 'default');
        return Cache::remember($cacheKey, 3000, function () use ($cacheKey) {
            $tokenEndpoint = $this->credentials['endpoints']['token'] ?? 'https://userauthapis.xbees.in/api/auth/generateToken';
            
            $payload = [
                'username' => $this->credentials['username'] ?? '',
                'password' => $this->credentials['password'] ?? '',
                'secretkey' => $this->credentials['secret_key'] ?? ''
            ];

            $response = Http::timeout(8)->post($tokenEndpoint, $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data['Token'] ?? $data['token'] ?? '';
            }

            throw new \Exception("Failed to generate Xpressbees token: " . $response->body());
        });
    }

    public function checkServiceability(string $pincode): bool
    {
        try {
            $url = $this->credentials['endpoints']['serviceability'] ?? 'https://xbmasterapi.xbees.in/expose/get/serviceabilitypincode/details';
            $token = $this->getToken();
            
            $response = Http::timeout(5)->withHeaders([
                'Token' => $token,
                'XBkey' => $this->credentials['xb_key'] ?? '',
                'Content-Type' => 'application/json'
            ])->post($url, [
                'BusinessUnit' => 'ECOM',
                'BusinessFlow' => 'FORWARD',
                'BusinessService' => 'SD',
                'pinCode' => $pincode
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === true) {
                    return true;
                }
                if (isset($data['ReturnCode']) && $data['ReturnCode'] === 100) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Fallback gracefully
        }
        
        $prefix = substr(trim($pincode), 0, 2);
        if ($prefix === '74') return false; 
        return true;
    }

    public function getNextAvailableAwb(): string
    {
        $awbRecord = \App\Models\XpressbeesAwb::where('status', 'available')->orderBy('id', 'asc')->lockForUpdate()->first();
        if ($awbRecord) {
            return $awbRecord->awb_number;
        }

        // If none available, generate a new batch
        $token = $this->getToken();
        $headers = [
            'Token' => $token,
            'XBkey' => $this->credentials['xb_key'] ?? '',
            'Content-Type' => 'application/json'
        ];

        $awbUrl = $this->credentials['endpoints']['awb_series'] ?? 'https://xbclientapi.xbees.in/POSTShipmentService.svc/AWBNumberSeriesGeneration';
        $awbGenResponse = Http::timeout(15)->withHeaders($headers)->post($awbUrl, [
            'BusinessUnit' => 'ECOM',
            'ServiceType' => 'FORWARD',
            'DeliveryType' => 'PREPAID',
            'Count' => 1
        ]);

        if ($awbGenResponse->successful() && $awbGenResponse->json('ReturnCode') === 100) {
            $batchId = $awbGenResponse->json('BatchID');
            $awbFetchUrl = $this->credentials['endpoints']['awb_generated'] ?? 'https://xbclientapi.xbees.in/TrackingService.svc/GetAWBNumberGeneratedSeries';
            $awbFetchResponse = Http::timeout(15)->withHeaders($headers)->post($awbFetchUrl, [
                'BusinessUnit' => 'ECOM',
                'ServiceType' => 'FORWARD',
                'BatchID' => $batchId
            ]);

            $series = $awbFetchResponse->json('AWBNoSeries');
            if (!empty($series) && is_array($series)) {
                $now = now();
                $rows = [];
                foreach ($series as $num) {
                    $rows[] = [
                        'awb_number' => (string)$num,
                        'status' => 'available',
                        'type' => 'AWB',
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
                foreach (array_chunk($rows, 200) as $chunk) {
                    \App\Models\XpressbeesAwb::insertOrIgnore($chunk);
                }
            }
        }

        $fresh = \App\Models\XpressbeesAwb::where('status', 'available')->orderBy('id', 'asc')->first();
        if ($fresh) {
            return $fresh->awb_number;
        }

        throw new \Exception("Could not allocate a valid Xpressbees AWB number.");
    }

    public function createShipment(array $shipmentDetails): array
    {
        try {
            $token = $this->getToken();
            $headers = [
                'Token' => $token,
                'XBkey' => $this->credentials['xb_key'] ?? '',
                'Content-Type' => 'application/json'
            ];

            // Allocate next available AWB
            $awbNumber = $shipmentDetails['awb_number'] ?? $this->getNextAvailableAwb();
            $orderNo = (string)($shipmentDetails['order_id'] ?? ('ORD-' . time()));
            $vendorCode = (string)($this->credentials['vendor_code'] ?? '38914');
            $accountName = $this->credentials['client_name'] ?? 'Onestall';

            $manifestUrl = $this->credentials['endpoints']['manifest'] ?? 'https://apishipmentmanifestation.xbees.in/shipmentmanifestation/forward';

            $manifestPayload = [
                'BusinessAccountName' => $accountName,
                'OrderNo' => $orderNo,
                'AirWayBillNO' => (string)$awbNumber,
                'ManifestID' => 'MF' . time() . rand(10, 99),
                'OrderType' => ($shipmentDetails['is_cod'] ?? false) ? 'COD' : 'PrePaid',
                'CollectibleAmount' => ($shipmentDetails['is_cod'] ?? false) ? (string)($shipmentDetails['invoice_value'] ?? 0) : '0',
                'DeclaredValue' => (string)max(100, $shipmentDetails['invoice_value'] ?? 100),
                'PickupType' => 'Vendor',
                'PickupVendorCode' => $vendorCode,
                'Quantity' => (string)($shipmentDetails['quantity'] ?? '1'),
                'ServiceType' => 'SD',
                'Weight' => (string)max(0.1, $shipmentDetails['weight_kg'] ?? 0.5),
                'Length' => (string)max(1, $shipmentDetails['length_cm'] ?? 10),
                'Breadth' => (string)max(1, $shipmentDetails['width_cm'] ?? 10),
                'Height' => (string)max(1, $shipmentDetails['height_cm'] ?? 10),

                'DropDetails' => [
                    'Addresses' => [[
                        'Name' => $shipmentDetails['receiver_name'] ?? 'Customer',
                        'Address' => $shipmentDetails['delivery_address'] ?? 'Customer Address',
                        'City' => $shipmentDetails['delivery_city'] ?? 'City',
                        'State' => $shipmentDetails['delivery_state'] ?? 'State',
                        'PinCode' => (string)($shipmentDetails['delivery_pincode'] ?? '110001'),
                        'Type' => 'Primary'
                    ]],
                    'ContactDetails' => [[
                        'Name' => $shipmentDetails['receiver_name'] ?? 'Customer',
                        'PhoneNo' => (string)($shipmentDetails['receiver_phone'] ?? '9999999999'),
                        'Email' => $shipmentDetails['receiver_email'] ?? 'customer@example.com',
                        'Type' => 'Primary'
                    ]]
                ],

                'PickupDetails' => [
                    'PickupVendorCode' => $vendorCode,
                    'Addresses' => [[
                        'Name' => $shipmentDetails['pickup_name'] ?? 'Warehouse',
                        'Address' => $shipmentDetails['pickup_address'] ?? 'Origin Warehouse',
                        'City' => $shipmentDetails['pickup_city'] ?? 'Gurugram',
                        'State' => $shipmentDetails['pickup_state'] ?? 'Haryana',
                        'PinCode' => (string)($shipmentDetails['pickup_pincode'] ?? '122015'),
                        'Type' => 'Primary'
                    ]],
                    'ContactDetails' => [[
                        'Name' => $shipmentDetails['pickup_name'] ?? 'Warehouse Contact',
                        'PhoneNo' => (string)($shipmentDetails['pickup_phone'] ?? '9999999999'),
                        'Email' => 'dispatch@onestall.com',
                        'Type' => 'Primary'
                    ]]
                ],

                'RTODetails' => [
                    'PickupVendorCode' => $vendorCode,
                    'Addresses' => [[
                        'Name' => $shipmentDetails['pickup_name'] ?? 'RTO Hub',
                        'Address' => $shipmentDetails['pickup_address'] ?? 'Origin Warehouse',
                        'City' => $shipmentDetails['pickup_city'] ?? 'Gurugram',
                        'State' => $shipmentDetails['pickup_state'] ?? 'Haryana',
                        'PinCode' => (string)($shipmentDetails['pickup_pincode'] ?? '122015'),
                        'Type' => 'Primary'
                    ]],
                    'ContactDetails' => [[
                        'Name' => $shipmentDetails['pickup_name'] ?? 'RTO Contact',
                        'PhoneNo' => (string)($shipmentDetails['pickup_phone'] ?? '9999999999'),
                        'Email' => 'rto@onestall.com',
                        'Type' => 'Primary'
                    ]]
                ]
            ];

            $manifestResponse = Http::timeout(15)->withHeaders($headers)->post($manifestUrl, $manifestPayload);

            if ($manifestResponse->successful()) {
                $resData = $manifestResponse->json();
                if (isset($resData['ReturnCode']) && $resData['ReturnCode'] === 100) {
                    // Mark AWB as used in database
                    \App\Models\XpressbeesAwb::where('awb_number', $awbNumber)->update([
                        'status' => 'used',
                        'order_id' => $orderNo
                    ]);

                    return [
                        'status' => 'success',
                        'awb' => $awbNumber,
                        'token_number' => $resData['TokenNumber'] ?? null,
                        'provider_response' => $resData
                    ];
                }

                return [
                    'status' => 'error',
                    'message' => $resData['ReturnMessage'] ?? ('Manifest rejected with code ' . ($resData['ReturnCode'] ?? 'unknown'))
                ];
            }

            return ['status' => 'error', 'message' => 'Manifest failed: ' . $manifestResponse->body()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Exception during Xpressbees shipment creation: ' . $e->getMessage()];
        }
    }

    public function trackShipment(string $awb): array
    {
        try {
            $token = $this->getToken();
            $trackUrl = $this->credentials['endpoints']['tracking_status'] ?? 'https://apishipmenttracking.xbees.in/GetCurrentShipmentStatus';
            
            $response = Http::timeout(8)->withHeaders([
                'Token' => $token,
                'XBkey' => $this->credentials['xb_key'] ?? '',
                'versionnumber' => 'v1',
                'Content-Type' => 'application/json'
            ])->post($trackUrl, [
                'AWBNumber' => $awb
            ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'tracking_data' => $response->json()
                ];
            }
            return ['status' => 'error', 'message' => 'Tracking failed: ' . $response->body()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Exception during tracking: ' . $e->getMessage()];
        }
    }

    public function cancelShipment(string $awb, string $reason = 'Cancelled by customer/seller'): array
    {
        try {
            $token = $this->getToken();
            $cancelUrl = $this->credentials['endpoints']['rto_notify'] ?? 'https://clientshipupdatesapi.xbees.in/forwardcancellation';

            $response = Http::timeout(10)->withHeaders([
                'Token' => $token,
                'XBkey' => $this->credentials['xb_key'] ?? '',
                'Content-Type' => 'application/json'
            ])->post($cancelUrl, [
                'ShippingID' => (string)$awb,
                'CancellationReason' => $reason
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['ReturnCode']) && $data['ReturnCode'] === 100) {
                    return [
                        'status' => 'success',
                        'message' => $data['ReturnMessage'] ?? 'Shipment cancelled successfully on Xpressbees',
                        'response' => $data
                    ];
                }
                return [
                    'status' => 'error',
                    'message' => $data['ReturnMessage'] ?? 'Xpressbees cancellation rejected'
                ];
            }

            return ['status' => 'error', 'message' => 'Xpressbees cancellation request failed: ' . $response->body()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Exception during Xpressbees cancellation: ' . $e->getMessage()];
        }
    }

    private function calculateFreight(float $weight, $zr): float
    {
        $cw = ceil($weight * 2) / 2;
        if ($cw < 0.5) $cw = 0.5;

        if ($cw <= 0.5) return $zr->first_0_5_kg;
        elseif ($cw <= 1.0) return $zr->first_0_5_kg + $zr->addl_0_5_kg;
        elseif ($cw <= 2.0) return $zr->first_2_kg;
        elseif ($cw <= 5.0) return $zr->first_2_kg + (ceil($cw - 2) * $zr->addl_1_kg_after_2);
        elseif ($cw <= 10.0) return $zr->first_5_kg + (ceil($cw - 5) * $zr->addl_1_kg_after_5);
        elseif ($cw <= 20.0) return $zr->first_10_kg + (ceil($cw - 10) * $zr->addl_1_kg_after_10);
        else return $zr->first_20_kg + (ceil($cw - 20) * $zr->addl_1_kg_after_20);
    }

    private function detectZone(string $fromPin, string $toPin): int
    {
        $from = trim($fromPin);
        $to   = trim($toPin);

        if ($from === $to) return 0;

        $fromPfx2 = substr($from, 0, 2);
        $toPfx2   = substr($to,   0, 2);

        $zone5 = ['79', '78', '77', '67', '68', '69', '18', '19'];
        if (in_array($toPfx2, $zone5)) return 4;

        $metros = ['40', '41', '11', '50', '56', '60', '70'];
        if (in_array($toPfx2, $metros) && !in_array($fromPfx2, ['40', '41', '11', '50', '56', '60', '70'])) return 2;

        if ($fromPfx2 === $toPfx2) return 1;

        return 3;
    }
}
