<?php
namespace App\Services;

use App\Models\Courier;
use App\Models\RateCard;
use App\Models\RateCardZone;

class PricingService
{
    /**
     * Calculate rate for a shipment based on fulfillment type (onestall or external)
     */
    public function calculateRate(string $fulfillment_type, string $pickup_pin, string $delivery_pin, float $physical_weight, float $l, float $b, float $h, bool $is_cod, float $invoice_value, bool $is_rto = false, bool $is_dto = false, int $qc_params = 0, ?int $provider_id = null, ?int $rateCardId = null)
    {
        if ($fulfillment_type === 'onestall') {
            return $this->calculateOneStallRate($pickup_pin, $delivery_pin, $physical_weight, $l, $b, $h, $is_cod, $invoice_value, $is_rto, $is_dto, $qc_params, $rateCardId);
        }

        if ($fulfillment_type === 'external' && $provider_id) {
            return $this->calculateExternalRate($provider_id, $pickup_pin, $delivery_pin, max($physical_weight, ($l * $b * $h) / 5000), $is_cod, $invoice_value);
        }

        throw new \Exception("Invalid fulfillment type or provider");
    }

    /**
     * Calculate Official OneStall Cargo Rate
     */
    public function calculateOneStallRate(string $pickup_pin, string $delivery_pin, float $physical_weight, float $l, float $b, float $h, bool $is_cod, float $invoice_value, bool $is_rto = false, bool $is_dto = false, int $qc_params = 0, ?int $rateCardId = null)
    {
        if ($rateCardId) {
            $rateCard = RateCard::find($rateCardId);
        } else {
            $rateCard = RateCard::whereNull('courier_id')->where('is_active', true)->latest()->first();
        }
        if (!$rateCard) {
            throw new \Exception("No active rate card found");
        }

        // Calculate Chargeable Weight
        $divisor = ($rateCard->volumetric_divisor && $rateCard->volumetric_divisor > 0) ? (float)$rateCard->volumetric_divisor : 5000;
        $volumetric_weight = ($l * $b * $h) / $divisor;
        $chargeable_weight = max($physical_weight, $volumetric_weight);
        
        // Determine Zone (Mock logic based on real zone definitions - typically done via DB/Pincode API)
        $zoneName = $this->determineZone($pickup_pin, $delivery_pin);
        
        $zoneRate = RateCardZone::where('rate_card_id', $rateCard->id)
            ->where('zone_name', 'LIKE', $zoneName . '%')
            ->first();

        if (!$zoneRate || $zoneRate->first_0_5_kg === null) {
            throw new \Exception("Pricing not available for $zoneName in current rate card");
        }

        // Base Freight Calculation using configured weight slabs
        $base_freight = $this->calculateBaseFreight($chargeable_weight, $zoneRate);

        // RTO / DTO Modifiers
        $rto_charge = 0;
        $dto_charge = 0;
        
        if ($is_rto) {
            // RTO = Same as forward
            $rto_charge = $base_freight;
            // Original base is still considered (so total = forward + RTO)
        }
        if ($is_dto) {
            $dto_charge = $base_freight * $rateCard->dto_multiplier;
        }

        // FSC Calculation
        $active_freight = $base_freight + $rto_charge + $dto_charge;
        $fsc_amount = $active_freight * ($rateCard->fsc_percent / 100);

        // COD Calculation using unified RateCard method
        $cod_charge = $rateCard->calculateCodCharge($invoice_value, $is_cod);

        // QC Charges (RVP)
        $qc_charge = 0;
        if ($qc_params > 0) {
            $qc_charge = $rateCard->qc_base_charge;
            if ($qc_params > $rateCard->qc_included_params) {
                $qc_charge += ($qc_params - $rateCard->qc_included_params) * $rateCard->qc_additional_param_charge;
            }
        }

        // GST
        $subtotal = $active_freight + $fsc_amount + $cod_charge + $qc_charge;
        $gst_amount = $subtotal * ($rateCard->gst_percent / 100);

        $total = $subtotal + $gst_amount;

        return [
            "zone" => $zoneName,
            "physical_weight" => round($physical_weight, 3),
            "volumetric_weight" => round($volumetric_weight, 3),
            "chargeable_weight" => round($chargeable_weight, 3),
            "base_freight" => round($base_freight, 2),
            "fsc_percent" => $rateCard->fsc_percent,
            "fsc_amount" => round($fsc_amount, 2),
            "cod_charge" => round($cod_charge, 2),
            "dto_charge" => round($dto_charge, 2),
            "rto_charge" => round($rto_charge, 2),
            "qc_charge" => round($qc_charge, 2),
            "gst" => round($gst_amount, 2),
            "total" => round($total, 2),
            "rate_card_version" => $rateCard->version_name
        ];
    }

    private function calculateBaseFreight(float $cw, RateCardZone $zr)
    {
        $cost = 0;

        if ($cw <= 0.5) {
            return $zr->first_0_5_kg;
        } elseif ($cw <= 1.0) {
            // First 0.5 + Addl 0.5
            return $zr->first_0_5_kg + $zr->addl_0_5_kg;
        } elseif ($cw <= 2.0) {
            return $zr->first_2_kg;
        } elseif ($cw <= 5.0) {
            if ($cw == 5.0) return $zr->first_5_kg;
            $addl_kg = ceil($cw - 2);
            return $zr->first_2_kg + ($addl_kg * $zr->addl_1_kg_after_2);
        } elseif ($cw <= 10.0) {
            if ($cw == 10.0) return $zr->first_10_kg;
            $addl_kg = ceil($cw - 5);
            return $zr->first_5_kg + ($addl_kg * $zr->addl_1_kg_after_5);
        } elseif ($cw <= 20.0) {
            if ($cw == 20.0) return $zr->first_20_kg;
            $addl_kg = ceil($cw - 10);
            return $zr->first_10_kg + ($addl_kg * $zr->addl_1_kg_after_10);
        } else {
            // > 20 KG
            $addl_kg = ceil($cw - 20);
            return $zr->first_20_kg + ($addl_kg * $zr->addl_1_kg_after_20);
        }
    }

    protected function determineZone(string $pickup_pin, string $delivery_pin)
    {
        // Real implementation would look up state/city matching logic.
        // For testing/mock purposes assuming standard routing based on prefix
        if (substr($pickup_pin, 0, 2) === substr($delivery_pin, 0, 2)) {
            return 'Zone 1';
        }
        
        if (substr($pickup_pin, 0, 1) === substr($delivery_pin, 0, 1)) {
            return 'Zone 2';
        }

        // Default to Zone 4 Rest of India
        return 'Zone 4';
    }

    public function calculateExternalRate($provider_id, $pickup_pin, $delivery_pin, $weight, bool $is_cod = false, float $invoice_value = 0)
    {
        $courier = Courier::find($provider_id);
        if (!$courier) {
            throw new \Exception("Courier not found");
        }

        if (stripos($courier->name, 'OneStall') !== false) {
            return $this->calculateOneStallRate($pickup_pin, $delivery_pin, $weight, 10, 10, 10, $is_cod, $invoice_value);
        }

        $serviceClass = "App\\Services\\Couriers\\" . str_replace([' ', '-'], '', ucwords(strtolower($courier->name))) . "Service";
        if (class_exists($serviceClass)) {
            $service = new $serviceClass($courier->api_credentials ?? []);
            // Pass is_cod and invoice_value so each courier can compute its own COD surcharge
            $rateResponse = $service->calculateRate($pickup_pin, $delivery_pin, $weight, $is_cod, $invoice_value);
            if ($rateResponse['status'] === 'success') {
                $baseFreight = $rateResponse['base_freight'] ?? $rateResponse['base_rate'] ?? $rateResponse['total'] ?? 0;
                
                // Admin Markup Calculation
                $markupAmount = 0;
                if ($courier->markup_type === 'percentage') {
                    $markupAmount = $baseFreight * ($courier->markup_value / 100);
                } elseif ($courier->markup_type === 'flat') {
                    $markupAmount = $courier->markup_value;
                }
                
                $finalBaseFreight = $baseFreight + $markupAmount;
                $fsc = $rateResponse['fsc'] ?? 0;
                $cod = $rateResponse['cod_charge'] ?? 0;
                
                $subtotal = $finalBaseFreight + $fsc + $cod;
                $gst = round($subtotal * 0.18, 2);
                $total = $subtotal + $gst;

                return [
                    "zone"             => $rateResponse['zone']      ?? "External",
                    "physical_weight"  => $weight,
                    "volumetric_weight"=> 0,
                    "chargeable_weight"=> $weight,
                    "base_freight"     => round($finalBaseFreight, 2),
                    "fsc_amount"       => round($fsc, 2),
                    "cod_charge"       => round($cod, 2),
                    "gst"              => round($gst, 2),
                    "total"            => round($total, 2),
                    "provider"         => $courier->name,
                    "breakdown"        => $rateResponse['breakdown'] ?? [],
                    "markup_applied"   => round($markupAmount, 2)
                ];
            }
        }

        throw new \Exception("External provider rate calculation failed");
    }

    /**
     * Legacy support
     */
    public function getAvailableRates(string $pickup_pin, string $delivery_pin, float $weight)
    {
        // Just calling external rate logic for legacy interface
        // Note: New architecture expects assigning provider directly or via serviceability engine.
        return [];
    }
}
