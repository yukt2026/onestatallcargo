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
        $this->credentials = $credentials;
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
            return ['status' => 'error', 'message' => 'Active rate card not found for Xpressbees'];
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

    public function createShipment(array $shipmentDetails): array
    {
        try {
            $token = $this->getToken();
            $headers = [
                'Token' => $token,
                'XBkey' => $this->credentials['xb_key'] ?? ''
            ];

            // Step 1: Generate AWB Batch
            $awbUrl = $this->credentials['endpoints']['awb_series'] ?? 'https://xbclientapi.xbees.in/POSTShipmentService.svc/AWBNumberSeriesGeneration';
            $awbGenResponse = Http::timeout(10)->withHeaders($headers)->post($awbUrl, [
                'BusinessUnit' => 'ECOM',
                'ServiceType' => 'FORWARD',
                'DeliveryType' => ($shipmentDetails['is_cod'] ?? false) ? 'COD' : 'PREPAID',
                'Count' => 1
            ]);

            if (!$awbGenResponse->successful() || $awbGenResponse->json('ReturnCode') !== 100) {
                return ['status' => 'error', 'message' => 'Failed to generate AWB Batch: ' . $awbGenResponse->body()];
            }
            
            $batchId = $awbGenResponse->json('BatchID');

            // Step 2: Fetch AWB from Batch
            $awbFetchUrl = 'https://xbclientapi.xbees.in/TrackingService.svc/GetAWBNumberGeneratedSeries';
            $awbFetchResponse = Http::timeout(10)->withHeaders($headers)->post($awbFetchUrl, [
                'BusinessUnit' => 'ECOM',
                'ServiceType' => 'FORWARD',
                'BatchID' => $batchId
            ]);

            if (!$awbFetchResponse->successful() || $awbFetchResponse->json('ReturnCode') !== 100) {
                return ['status' => 'error', 'message' => 'Failed to fetch AWB from Batch: ' . $awbFetchResponse->body()];
            }

            $awbSeries = $awbFetchResponse->json('AWBNoSeries');
            if (empty($awbSeries) || !is_array($awbSeries)) {
                return ['status' => 'error', 'message' => 'No AWB found in batch response'];
            }

            $awbNumber = $awbSeries[0];

            // Step 2: Manifest Forward
            $manifestUrl = $this->credentials['endpoints']['manifest'] ?? 'https://apishipmentmanifestation.xbees.in/shipmentmanifestation/forward';
            
            $manifestPayload = [
                'ManifestDetails' => [
                    [
                        'ManifestID' => 'MF' . time() . rand(100, 999),
                        'AirWayBillNO' => $awbNumber,
                        'BusinessAccountName' => 'Onestall', // Usually ClientName or Business Account Name
                        'OrderNo' => $shipmentDetails['order_id'] ?? uniqid(),
                        'OrderType' => ($shipmentDetails['is_cod'] ?? false) ? 'COD' : 'PrePaid',
                        'CollectibleAmount' => ($shipmentDetails['is_cod'] ?? false) ? (string)($shipmentDetails['invoice_value'] ?? 0) : '0',
                        'DeclaredValue' => (string)($shipmentDetails['invoice_value'] ?? 0),
                        'PickupType' => 'Vendor',
                        'Quantity' => '1',
                        'ServiceType' => 'SD', // SD typically means Standard Delivery
                        'Weight' => (string)($shipmentDetails['weight_kg'] ?? 0.5),
                        'Length' => (string)($shipmentDetails['length_cm'] ?? 10),
                        'Breadth' => (string)($shipmentDetails['width_cm'] ?? 10),
                        'Height' => (string)($shipmentDetails['height_cm'] ?? 10),
                        
                        'DropDetails' => [
                            'Addresses' => [
                                [
                                    'AddressName' => $shipmentDetails['receiver_name'] ?? 'Customer',
                                    'AddressAddress1' => $shipmentDetails['delivery_address'] ?? 'Address',
                                    'AddressAddress2' => '',
                                    'AddressCity' => $shipmentDetails['delivery_city'] ?? 'City',
                                    'AddressState' => $shipmentDetails['delivery_state'] ?? 'State',
                                    'AddressPincode' => $shipmentDetails['delivery_pincode'] ?? '000000',
                                    'AddressType' => 'Drop'
                                ]
                            ],
                            'ContactDetails' => [
                                [
                                    'ContactName' => $shipmentDetails['receiver_name'] ?? 'Customer',
                                    'ContactPhoneNo' => $shipmentDetails['receiver_phone'] ?? '9999999999',
                                    'ContactEmailId' => 'customer@example.com',
                                    'ContactType' => 'Drop'
                                ]
                            ]
                        ],
                        
                        'PickupDetails' => [
                            'Addresses' => [
                                [
                                    'AddressName' => $shipmentDetails['pickup_name'] ?? 'Pickup Name',
                                    'AddressAddress1' => $shipmentDetails['pickup_address'] ?? 'Pickup Address',
                                    'AddressAddress2' => '',
                                    'AddressCity' => $shipmentDetails['pickup_city'] ?? 'City',
                                    'AddressState' => $shipmentDetails['pickup_state'] ?? 'State',
                                    'AddressPincode' => $shipmentDetails['pickup_pincode'] ?? '000000',
                                    'AddressType' => 'Pickup'
                                ]
                            ],
                            'ContactDetails' => [
                                [
                                    'ContactName' => $shipmentDetails['pickup_name'] ?? 'Pickup Contact',
                                    'ContactPhoneNo' => $shipmentDetails['pickup_phone'] ?? '9999999999',
                                    'ContactEmailId' => 'vendor@onestall.com',
                                    'ContactType' => 'Pickup'
                                ]
                            ]
                        ],
                        
                        'RTODetails' => [
                            'Addresses' => [
                                [
                                    'AddressName' => $shipmentDetails['pickup_name'] ?? 'RTO Name',
                                    'AddressAddress1' => $shipmentDetails['pickup_address'] ?? 'RTO Address',
                                    'AddressAddress2' => '',
                                    'AddressCity' => $shipmentDetails['pickup_city'] ?? 'City',
                                    'AddressState' => $shipmentDetails['pickup_state'] ?? 'State',
                                    'AddressPincode' => $shipmentDetails['pickup_pincode'] ?? '000000',
                                    'AddressType' => 'RTO'
                                ]
                            ],
                            'ContactDetails' => [
                                [
                                    'ContactName' => $shipmentDetails['pickup_name'] ?? 'RTO Contact',
                                    'ContactPhoneNo' => $shipmentDetails['pickup_phone'] ?? '9999999999',
                                    'ContactEmailId' => 'vendor@onestall.com',
                                    'ContactType' => 'RTO'
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $manifestResponse = Http::timeout(12)->withHeaders($headers)->post($manifestUrl, $manifestPayload);

            if ($manifestResponse->successful()) {
                return [
                    'status' => 'success',
                    'awb' => $awbNumber,
                    'provider_response' => $manifestResponse->json()
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
                'XBkey' => $this->credentials['xb_key'] ?? ''
            ])->post($trackUrl, [
                'AWBNo' => $awb
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
