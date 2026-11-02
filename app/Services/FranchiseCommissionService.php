<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Franchise;
use App\Models\FranchiseCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FranchiseCommissionService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Calculate and credit delivery commission for District Franchise and State Royalty.
     * Transaction-safe and idempotent: duplicate delivery events will never double-credit.
     */
    public function processDeliveryCommissions(Shipment $shipment): array
    {
        return DB::transaction(function () use ($shipment) {
            // Re-fetch shipment with row lock
            $lockedShipment = Shipment::lockForUpdate()->find($shipment->id);
            if (!$lockedShipment || strtolower(trim($lockedShipment->status)) !== 'delivered') {
                return ['success' => false, 'reason' => 'Shipment is not delivered.'];
            }

            $results = [
                'district_commission' => null,
                'state_royalty' => null,
            ];

            // 1. Process District Delivery Commission
            $districtFranchiseId = $lockedShipment->franchise_id;
            if ($districtFranchiseId) {
                $districtFranchise = Franchise::lockForUpdate()->find($districtFranchiseId);

                if ($districtFranchise && $districtFranchise->status === 'approved' && $districtFranchise->isDistrict()) {
                    // IDEMPOTENCY CHECK: Check existing ledger entry
                    $existingComm = FranchiseCommission::where('franchise_id', $districtFranchise->id)
                        ->where('shipment_id', $lockedShipment->id)
                        ->where('commission_type', 'district_delivery')
                        ->first();

                    if (!$existingComm) {
                        $rate = (float) $districtFranchise->delivery_commission_rate;
                        $rateType = $districtFranchise->delivery_commission_type ?: 'flat';
                        $amount = 0.00;

                        if ($rate > 0) {
                            if ($rateType === 'per_kg') {
                                $weight = max(0.5, (float) $lockedShipment->weight_kg);
                                $amount = round($rate * $weight, 2);
                            } elseif ($rateType === 'percent') {
                                $freight = (float) $lockedShipment->shipping_charge;
                                $amount = round(($rate / 100) * $freight, 2);
                            } else {
                                // 'flat'
                                $amount = round($rate, 2);
                            }
                        }

                        if ($amount > 0) {
                            $commission = FranchiseCommission::create([
                                'franchise_id' => $districtFranchise->id,
                                'shipment_id' => $lockedShipment->id,
                                'commission_type' => 'district_delivery',
                                'rate_type' => $rateType,
                                'rate_applied' => $rate,
                                'amount' => $amount,
                                'status' => 'credited',
                                'credited_at' => now(),
                                'notes' => "Delivery commission for AWB #{$lockedShipment->awb_number}",
                            ]);

                            // Credit franchise manager wallet
                            if ($districtFranchise->user_id) {
                                $this->walletService->credit(
                                    $districtFranchise->user_id,
                                    $amount,
                                    "Delivery Commission for AWB #{$lockedShipment->awb_number}",
                                    "COMM-DEL-{$lockedShipment->id}-{$districtFranchise->id}"
                                );
                            }

                            $results['district_commission'] = $commission;
                        }
                    } else {
                        $results['district_commission'] = $existingComm; // Idempotent response
                    }
                }
            }

            // 2. Process State Franchise Royalty
            $stateFranchiseId = $lockedShipment->state_franchise_id;
            if (!$stateFranchiseId && !empty($districtFranchise)) {
                $stateFranchiseId = $districtFranchise->parent_franchise_id;
            }

            if ($stateFranchiseId) {
                $stateFranchise = Franchise::lockForUpdate()->find($stateFranchiseId);

                if ($stateFranchise && $stateFranchise->status === 'approved' && $stateFranchise->isState()) {
                    // IDEMPOTENCY CHECK
                    $existingRoyalty = FranchiseCommission::where('franchise_id', $stateFranchise->id)
                        ->where('shipment_id', $lockedShipment->id)
                        ->where('commission_type', 'state_royalty')
                        ->first();

                    if (!$existingRoyalty) {
                        $rate = (float) $stateFranchise->state_royalty_rate;
                        $rateType = $stateFranchise->state_royalty_type ?: 'percent';
                        $amount = 0.00;

                        if ($rate > 0) {
                            if ($rateType === 'per_kg') {
                                $weight = max(0.5, (float) $lockedShipment->weight_kg);
                                $amount = round($rate * $weight, 2);
                            } elseif ($rateType === 'flat') {
                                $amount = round($rate, 2);
                            } else {
                                // Default 'percent' of shipping charge
                                $freight = (float) $lockedShipment->shipping_charge;
                                $amount = round(($rate / 100) * $freight, 2);
                            }
                        }

                        if ($amount > 0) {
                            $royalty = FranchiseCommission::create([
                                'franchise_id' => $stateFranchise->id,
                                'shipment_id' => $lockedShipment->id,
                                'commission_type' => 'state_royalty',
                                'rate_type' => $rateType,
                                'rate_applied' => $rate,
                                'amount' => $amount,
                                'status' => 'credited',
                                'credited_at' => now(),
                                'notes' => "State Royalty for AWB #{$lockedShipment->awb_number}",
                            ]);

                            // Credit state franchise manager wallet
                            if ($stateFranchise->user_id) {
                                $this->walletService->credit(
                                    $stateFranchise->user_id,
                                    $amount,
                                    "State Royalty for AWB #{$lockedShipment->awb_number}",
                                    "ROYALTY-{$lockedShipment->id}-{$stateFranchise->id}"
                                );
                            }

                            $results['state_royalty'] = $royalty;
                        }
                    } else {
                        $results['state_royalty'] = $existingRoyalty; // Idempotent response
                    }
                }
            }

            return ['success' => true, 'data' => $results];
        });
    }

    /**
     * Calculate and credit first-mile pickup commission for District Franchise
     */
    public function processPickupCommissions(Shipment $shipment): array
    {
        return DB::transaction(function () use ($shipment) {
            $lockedShipment = Shipment::lockForUpdate()->find($shipment->id);
            if (!$lockedShipment) {
                return ['success' => false, 'reason' => 'Shipment not found.'];
            }

            $pickupFranchiseId = $lockedShipment->pickup_franchise_id;
            if (!$pickupFranchiseId && $lockedShipment->pickup_pincode) {
                $pickupFranchiseId = app(ServiceabilityService::class)->getFranchiseForPincode($lockedShipment->pickup_pincode);
            }

            if (!$pickupFranchiseId) {
                return ['success' => false, 'reason' => 'No pickup franchise assigned.'];
            }

            $franchise = Franchise::lockForUpdate()->find($pickupFranchiseId);
            if (!$franchise || $franchise->status !== 'approved' || !$franchise->isDistrict()) {
                return ['success' => false, 'reason' => 'Franchise not eligible for pickup commission.'];
            }

            // IDEMPOTENCY CHECK
            $existing = FranchiseCommission::where('franchise_id', $franchise->id)
                ->where('shipment_id', $lockedShipment->id)
                ->where('commission_type', 'district_pickup')
                ->first();

            if ($existing) {
                return ['success' => true, 'data' => $existing]; // Already credited
            }

            $rate = (float) $franchise->pickup_commission_rate;
            $rateType = $franchise->pickup_commission_type ?: 'flat';
            $amount = 0.00;

            if ($rate > 0) {
                if ($rateType === 'per_kg') {
                    $weight = max(0.5, (float) $lockedShipment->weight_kg);
                    $amount = round($rate * $weight, 2);
                } elseif ($rateType === 'percent') {
                    $freight = (float) $lockedShipment->shipping_charge;
                    $amount = round(($rate / 100) * $freight, 2);
                } else {
                    $amount = round($rate, 2);
                }
            }

            if ($amount > 0) {
                $commission = FranchiseCommission::create([
                    'franchise_id' => $franchise->id,
                    'shipment_id' => $lockedShipment->id,
                    'commission_type' => 'district_pickup',
                    'rate_type' => $rateType,
                    'rate_applied' => $rate,
                    'amount' => $amount,
                    'status' => 'credited',
                    'credited_at' => now(),
                    'notes' => "Pickup commission for AWB #{$lockedShipment->awb_number}",
                ]);

                if ($franchise->user_id) {
                    $this->walletService->credit(
                        $franchise->user_id,
                        $amount,
                        "Pickup Commission for AWB #{$lockedShipment->awb_number}",
                        "COMM-PKP-{$lockedShipment->id}-{$franchise->id}"
                    );
                }

                return ['success' => true, 'data' => $commission];
            }

            return ['success' => true, 'data' => null];
        });
    }
}
