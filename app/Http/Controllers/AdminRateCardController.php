<?php
namespace App\Http\Controllers;

use App\Models\RateCard;
use App\Models\RateCardZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PricingService;

class AdminRateCardController extends Controller {

    public function index(Request $request)
    {
        $query = RateCard::withCount('zones')->orderBy('created_at', 'desc');
        
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $rateCards = $query->paginate(15)->withQueryString();
        
        return view('admin.ratecards.index', compact('rateCards'));
    }

    public function create()
    {
        // Default official values for a new rate card (pre-filled for convenience, but fully editable)
        return view('admin.ratecards.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'version_name' => 'required|string|max:255',
            'effective_from' => 'required|date',
            'fsc_percent' => 'required|numeric|min:0',
            'cod_min_charge' => 'required|numeric|min:0',
            'cod_threshold_amount' => 'nullable|numeric|min:0',
            'cod_percent' => 'required|numeric|min:0',
            'dto_multiplier' => 'required|numeric|min:0',
            'qc_base_charge' => 'required|numeric|min:0',
            'qc_additional_param_charge' => 'required|numeric|min:0',
            'volumetric_divisor' => 'required|numeric|min:1',
            'gst_percent' => 'required|numeric|min:0',
            'zones' => 'required|array',
            'zones.*.zone_name' => 'required|string',
            'zones.*.first_0_5_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_0_5_kg' => 'nullable|numeric|min:0',
            'zones.*.first_2_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_1_kg_after_2' => 'nullable|numeric|min:0',
            'zones.*.first_5_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_1_kg_after_5' => 'nullable|numeric|min:0',
            'zones.*.first_10_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_1_kg_after_10' => 'nullable|numeric|min:0',
            'zones.*.first_20_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_1_kg_after_20' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // If this is set to active, deactivate others (or we can just leave it inactive by default)
                $rateCard = RateCard::create([
                    'version_name' => $validated['version_name'],
                    'effective_from' => $validated['effective_from'],
                    'is_active' => false, // New cards are inactive by default
                    'fsc_percent' => $validated['fsc_percent'],
                    'cod_min_charge' => $validated['cod_min_charge'],
                    'cod_threshold_amount' => $validated['cod_threshold_amount'] ?? 0.00,
                    'cod_percent' => $validated['cod_percent'],
                    'dto_multiplier' => $validated['dto_multiplier'],
                    'qc_base_charge' => $validated['qc_base_charge'],
                    'qc_included_params' => 3, // Hardcoded for now based on rules
                    'qc_additional_param_charge' => $validated['qc_additional_param_charge'],
                    'volumetric_divisor' => $validated['volumetric_divisor'],
                    'gst_percent' => $validated['gst_percent']
                ]);

                foreach ($validated['zones'] as $zone) {
                    // Only save if first_0_5_kg is present and not an empty string (allow 0 as a valid rate)
                    if (isset($zone['first_0_5_kg']) && $zone['first_0_5_kg'] !== null) {
                        $zone['rate_card_id'] = $rateCard->id;
                        RateCardZone::create($zone);
                    }
                }
            });

            return redirect()->route('admin.ratecards.index')->with('success', 'Rate Card created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $rateCard = RateCard::with('zones')->findOrFail($id);
        return view('admin.ratecards.show', compact('rateCard'));
    }

    public function edit($id)
    {
        $rateCard = RateCard::with('zones')->findOrFail($id);
        if ($rateCard->is_active) {
            return back()->with('error', 'Cannot edit an active rate card. Duplicate it to create a new version.');
        }
        return view('admin.ratecards.edit', compact('rateCard'));
    }

    public function update(Request $request, $id)
    {
        $rateCard = RateCard::findOrFail($id);
        
        if ($rateCard->is_active) {
            return back()->with('error', 'Cannot edit an active rate card.');
        }

        $validated = $request->validate([
            'version_name' => 'required|string|max:255',
            'effective_from' => 'required|date',
            'fsc_percent' => 'required|numeric|min:0',
            'cod_min_charge' => 'required|numeric|min:0',
            'cod_threshold_amount' => 'nullable|numeric|min:0',
            'cod_percent' => 'required|numeric|min:0',
            'dto_multiplier' => 'required|numeric|min:0',
            'qc_base_charge' => 'required|numeric|min:0',
            'qc_additional_param_charge' => 'required|numeric|min:0',
            'volumetric_divisor' => 'required|numeric|min:1',
            'gst_percent' => 'required|numeric|min:0',
            'zones' => 'required|array',
            'zones.*.zone_name' => 'required|string',
            'zones.*.first_0_5_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_0_5_kg' => 'nullable|numeric|min:0',
            'zones.*.first_2_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_1_kg_after_2' => 'nullable|numeric|min:0',
            'zones.*.first_5_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_1_kg_after_5' => 'nullable|numeric|min:0',
            'zones.*.first_10_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_1_kg_after_10' => 'nullable|numeric|min:0',
            'zones.*.first_20_kg' => 'nullable|numeric|min:0',
            'zones.*.addl_1_kg_after_20' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated, $rateCard) {
                $rateCard->update([
                    'version_name' => $validated['version_name'],
                    'effective_from' => $validated['effective_from'],
                    'fsc_percent' => $validated['fsc_percent'],
                    'cod_min_charge' => $validated['cod_min_charge'],
                    'cod_threshold_amount' => $validated['cod_threshold_amount'] ?? 0.00,
                    'cod_percent' => $validated['cod_percent'],
                    'dto_multiplier' => $validated['dto_multiplier'],
                    'qc_base_charge' => $validated['qc_base_charge'],
                    'qc_additional_param_charge' => $validated['qc_additional_param_charge'],
                    'volumetric_divisor' => $validated['volumetric_divisor'],
                    'gst_percent' => $validated['gst_percent']
                ]);

                // Delete old zones
                $rateCard->zones()->delete();

                // Re-create zones
                foreach ($validated['zones'] as $zone) {
                    if (isset($zone['first_0_5_kg']) && $zone['first_0_5_kg'] !== null) {
                        $zone['rate_card_id'] = $rateCard->id;
                        RateCardZone::create($zone);
                    }
                }
            });

            return redirect()->route('admin.ratecards.index')->with('success', 'Rate Card updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function activate($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $rateCard = RateCard::findOrFail($id);
                
                // Deactivate all others for the same courier (or null)
                RateCard::where('courier_id', $rateCard->courier_id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
                
                // Activate selected
                $rateCard->is_active = true;
                $rateCard->save();
            });

            return back()->with('success', 'Rate Card activated successfully. All future shipments will use this pricing.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function duplicate($id)
    {
        try {
            $newCard = DB::transaction(function () use ($id) {
                $original = RateCard::with('zones')->findOrFail($id);
                
                $new = $original->replicate();
                $new->version_name = $original->version_name . ' (Copy)';
                $new->is_active = false;
                $new->effective_from = now();
                $new->save();

                foreach ($original->zones as $zone) {
                    $newZone = $zone->replicate();
                    $newZone->rate_card_id = $new->id;
                    $newZone->save();
                }

                return $new;
            });

            return redirect()->route('admin.ratecards.edit', $newCard->id)->with('success', 'Rate Card duplicated. You can now edit the new version.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function preview(Request $request, $id)
    {
        $validated = $request->validate([
            'pickup_pincode' => 'required|string',
            'delivery_pincode' => 'required|string',
            'weight_kg' => 'required|numeric',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'is_cod' => 'required|boolean',
            'invoice_value' => 'required|numeric'
        ]);

        $rateCard = RateCard::with('zones')->findOrFail($id);

        try {
            // Need a way to inject this specific RateCard into the pricing calculation 
            // without modifying the global PricingService heavily.
            // Let's pass the rate_card_id as an optional parameter to PricingService
            $routing = app(\App\Services\ServiceabilityService::class)->determineRouting($validated['delivery_pincode']);
            
            $rateData = app(\App\Services\PricingService::class)->calculateRate(
                $routing['fulfillment_type'],
                $validated['pickup_pincode'],
                $validated['delivery_pincode'],
                $validated['weight_kg'],
                $validated['length_cm'] ?? 10,
                $validated['width_cm'] ?? 10,
                $validated['height_cm'] ?? 10,
                $validated['is_cod'],
                $validated['invoice_value'],
                false, false, 0,
                $routing['provider_id'],
                $id // Pass the specific rate card ID to override the active one
            );

            return response()->json([
                'success' => true,
                'data' => $rateData,
                'routing' => $routing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        $rateCard = RateCard::findOrFail($id);
        if ($rateCard->is_active) {
            return back()->with('error', 'Cannot delete an active rate card. Please activate another rate card first.');
        }
        $rateCard->zones()->delete();
        $rateCard->delete();
        return back()->with('success', 'Rate card deleted successfully.');
    }
}
