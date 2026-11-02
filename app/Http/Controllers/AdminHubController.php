<?php

namespace App\Http\Controllers;

use App\Models\Hub;
use App\Models\User;
use App\Models\Franchise;
use App\Models\ServiceablePincode;
use App\Models\Shipment;
use App\Models\FranchiseCommission;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class AdminHubController extends Controller
{
    public function index()
    {
        $hubs = Hub::with('manager')->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $managers = User::where('role', 'franchise')->get();
        $pendingFranchises = Franchise::where('status', 'pending')->orderBy('created_at', 'desc')->get();

        // Hierarchy data for State and District franchises
        $stateFranchises = Franchise::with(['user', 'districtFranchises.user'])
            ->where('franchise_level', 'state')
            ->orderBy('state')
            ->get();

        $allDistrictFranchises = Franchise::with(['user', 'parentFranchise'])
            ->where('franchise_level', 'district')
            ->orderBy('state')
            ->orderBy('district')
            ->get();

        $allFranchiseIds = Franchise::pluck('id')->toArray();

        // 1. Single aggregate query for all destination shipments
        $shipmentStats = Shipment::selectRaw('
            franchise_id,
            COUNT(*) as total_count,
            SUM(CASE WHEN LOWER(TRIM(status)) = "delivered" THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN LOWER(TRIM(status)) IN ("pending", "manifested", "in transit", "in_transit", "transit", "received", "out for delivery", "out_for_delivery", "pickup scheduled", "pickup_scheduled", "new", "booked") THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN LOWER(TRIM(status)) = "ndr" OR status LIKE "RTO%" OR status LIKE "rto%" THEN 1 ELSE 0 END) as ndr_rto_count,
            SUM(CASE WHEN LOWER(TRIM(status)) = "delivered" AND (payment_type = "COD" OR is_cod = 1) AND (cod_remitted = 0 OR cod_remitted IS NULL) THEN COALESCE(cod_amount, invoice_value, 0) ELSE 0 END) as pending_cod
        ')
        ->whereIn('franchise_id', $allFranchiseIds)
        ->groupBy('franchise_id')
        ->get()
        ->keyBy('franchise_id');

        // 2. Single aggregate query for all commissions
        $commissionStats = FranchiseCommission::selectRaw('
            franchise_id,
            SUM(CASE WHEN status = "credited" THEN amount ELSE 0 END) as credited_amount
        ')
        ->whereIn('franchise_id', $allFranchiseIds)
        ->groupBy('franchise_id')
        ->get()
        ->keyBy('franchise_id');

        // 3. Attach metrics to each District Franchise
        foreach ($allDistrictFranchises as $distF) {
            $stats = $shipmentStats->get($distF->id);
            $comm = $commissionStats->get($distF->id);

            $distF->metrics = (object) [
                'total' => (int) ($stats->total_count ?? 0),
                'delivered' => (int) ($stats->delivered_count ?? 0),
                'pending' => (int) ($stats->pending_count ?? 0),
                'ndr_rto' => (int) ($stats->ndr_rto_count ?? 0),
                'pending_cod' => (float) ($stats->pending_cod ?? 0),
                'commission_earned' => (float) ($comm->credited_amount ?? 0),
                'wallet_balance' => (float) ($distF->user?->wallet_balance ?? 0),
            ];
        }

        // 4. Attach rollups to each State Franchise
        foreach ($stateFranchises as $stateF) {
            $subDistricts = $stateF->districtFranchises;
            $subIds = $subDistricts->pluck('id')->toArray();

            $distDelivered = 0;
            $distPending = 0;
            $distNdrRto = 0;
            $distCod = 0;
            $distTotal = 0;
            $distCommissions = 0;

            foreach ($subDistricts as $sDist) {
                $sStats = $shipmentStats->get($sDist->id);
                $sComm = $commissionStats->get($sDist->id);

                $sDist->metrics = (object) [
                    'total' => (int) ($sStats->total_count ?? 0),
                    'delivered' => (int) ($sStats->delivered_count ?? 0),
                    'pending' => (int) ($sStats->pending_count ?? 0),
                    'ndr_rto' => (int) ($sStats->ndr_rto_count ?? 0),
                    'pending_cod' => (float) ($sStats->pending_cod ?? 0),
                    'commission_earned' => (float) ($sComm->credited_amount ?? 0),
                    'wallet_balance' => (float) ($sDist->user?->wallet_balance ?? 0),
                ];

                $distTotal += $sDist->metrics->total;
                $distDelivered += $sDist->metrics->delivered;
                $distPending += $sDist->metrics->pending;
                $distNdrRto += $sDist->metrics->ndr_rto;
                $distCod += $sDist->metrics->pending_cod;
                $distCommissions += $sDist->metrics->commission_earned;
            }

            // Also check direct shipments where state_franchise_id is set but franchise_id is not in subIds
            $directStateQuery = Shipment::where('state_franchise_id', $stateF->id);
            if (!empty($subIds)) {
                $directStateQuery->whereNotIn('franchise_id', $subIds);
            }
            $directDelivered = (clone $directStateQuery)->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->count();
            $directPending = (clone $directStateQuery)->whereIn('status', ['Pending', 'pending', 'Manifested', 'manifested', 'In Transit', 'in_transit', 'transit', 'Received', 'Out for Delivery', 'out_for_delivery', 'Pickup Scheduled', 'pickup_scheduled', 'new', 'booked'])->count();
            $directNdrRto = (clone $directStateQuery)->where(function($q) { $q->where('status', 'NDR')->orWhere('status', 'ndr')->orWhere('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%'); })->count();
            $directCod = (clone $directStateQuery)->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->where(function($q) { $q->where('payment_type', 'COD')->orWhere('is_cod', 1); })->where(function($q) { $q->where('cod_remitted', 0)->orWhereNull('cod_remitted'); })->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));
            $directTotal = $directStateQuery->count();

            $stateRoyalty = (float) ($commissionStats->get($stateF->id)?->credited_amount ?? 0);

            $stateF->metrics = (object) [
                'total' => $distTotal + $directTotal,
                'delivered' => $distDelivered + $directDelivered,
                'pending' => $distPending + $directPending,
                'ndr_rto' => $distNdrRto + $directNdrRto,
                'pending_cod' => $distCod + (float) $directCod,
                'state_royalty' => $stateRoyalty,
                'network_commissions' => $distCommissions + $stateRoyalty,
                'wallet_balance' => (float) ($stateF->user?->wallet_balance ?? 0),
                'subordinate_count' => $subDistricts->count(),
            ];
        }

        $independentDistricts = $allDistrictFranchises->whereNull('parent_franchise_id');

        return view('admin.hubs.index', compact('hubs', 'managers', 'pendingFranchises', 'stateFranchises', 'allDistrictFranchises', 'independentDistricts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hub_code' => 'required|string|unique:hubs,hub_code|max:20',
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'capacity' => 'required|numeric|min:1',
            'manager_id' => 'nullable|exists:users,id'
        ]);

        Hub::create([
            'hub_code' => strtoupper($validated['hub_code']),
            'name' => $validated['name'],
            'city' => $validated['city'],
            'pincode' => $validated['pincode'],
            'capacity' => $validated['capacity'],
            'manager_id' => $validated['manager_id'],
            'is_active' => true
        ]);

        return back()->with('success', 'Hub created successfully.');
    }

    public function toggle($id)
    {
        $hub = Hub::findOrFail($id);
        $hub->is_active = !$hub->is_active;
        $hub->save();

        return back()->with('success', 'Hub status updated.');
    }

    /**
     * Create a State-level franchise
     */
    public function storeStateFranchise(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'state' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'required|string',
            'state_royalty_type' => 'required|in:flat,per_kg,percent',
            'state_royalty_rate' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'franchise',
                'status' => 'active',
            ]);

            $franchise = Franchise::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'owner_name' => $validated['owner_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'status' => 'approved',
                'franchise_level' => 'state',
                'parent_franchise_id' => null,
                'state_royalty_type' => $validated['state_royalty_type'],
                'state_royalty_rate' => $validated['state_royalty_rate'],
            ]);

            // Automatically create a physical Hub for State linehaul operations
            $hubCode = 'ST-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $validated['state']), 0, 3)) . '-' . rand(10, 99);
            Hub::create([
                'hub_code' => $hubCode,
                'name' => $validated['company_name'] . ' (State Hub)',
                'city' => $validated['city'],
                'pincode' => '000000',
                'capacity' => 10000,
                'manager_id' => $user->id,
                'is_active' => true,
            ]);

            return back()->with('success', "State Franchise '{$franchise->company_name}' registered successfully with Hub Code {$hubCode}.");
        });
    }

    /**
     * Create a District-level franchise linked to a State franchise
     */
    public function storeDistrictFranchise(Request $request)
    {
        $validated = $request->validate([
            'parent_franchise_id' => 'required|exists:franchises,id',
            'district' => 'required|string|max:100',
            'company_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'city' => 'required|string|max:100',
            'address' => 'required|string',
            'serviceable_pincodes' => 'required|string',
            'delivery_commission_type' => 'required|in:flat,per_kg,percent',
            'delivery_commission_rate' => 'required|numeric|min:0',
            'pickup_commission_type' => 'required|in:flat,per_kg,percent',
            'pickup_commission_rate' => 'required|numeric|min:0',
        ]);

        $parent = Franchise::findOrFail($validated['parent_franchise_id']);
        if (!$parent->isState()) {
            return back()->withInput()->with('error', 'Selected parent franchise must be a State-level franchise.');
        }

        // Parse and clean pincodes
        $pincodesArray = array_values(array_filter(array_unique(array_map('trim', explode(',', $validated['serviceable_pincodes'])))));

        if (empty($pincodesArray)) {
            return back()->withInput()->with('error', 'Please provide at least one valid pincode.');
        }

        // Territory Conflict Check: ensure pincodes are not already actively assigned to another District Franchise
        $existingPins = ServiceablePincode::whereIn('pincode', $pincodesArray)
            ->where('is_active', true)
            ->with('franchise')
            ->get();

        if ($existingPins->isNotEmpty()) {
            $conflicts = $existingPins->map(function ($p) {
                $owner = $p->franchise ? $p->franchise->company_name : 'Another Franchise';
                return "{$p->pincode} ({$owner})";
            })->implode(', ');

            return back()->withInput()->with('error', "Territory Conflict: Pincode(s) are already actively assigned: {$conflicts}. Duplicate active territory assignments are not permitted.");
        }

        return DB::transaction(function () use ($validated, $parent, $pincodesArray) {
            $user = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'franchise',
                'status' => 'active',
            ]);

            $franchise = Franchise::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'owner_name' => $validated['owner_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'district' => $validated['district'],
                'state' => $parent->state, // inherit state from parent
                'status' => 'approved',
                'franchise_level' => 'district',
                'parent_franchise_id' => $parent->id,
                'serviceable_pincodes' => $pincodesArray,
                'delivery_commission_type' => $validated['delivery_commission_type'],
                'delivery_commission_rate' => $validated['delivery_commission_rate'],
                'pickup_commission_type' => $validated['pickup_commission_type'],
                'pickup_commission_rate' => $validated['pickup_commission_rate'],
            ]);

            // Sync into ServiceablePincode table
            foreach ($pincodesArray as $pin) {
                ServiceablePincode::updateOrCreate(
                    ['pincode' => $pin],
                    [
                        'franchise_id' => $franchise->id,
                        'city' => $validated['city'],
                        'state' => $parent->state,
                        'is_active' => true,
                    ]
                );
            }

            // Create District Hub for local operations
            $hubCode = 'DST-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $validated['district']), 0, 3)) . '-' . rand(10, 99);
            Hub::create([
                'hub_code' => $hubCode,
                'name' => $validated['company_name'] . ' (' . $validated['district'] . ' District)',
                'city' => $validated['city'],
                'pincode' => $pincodesArray[0] ?? '000000',
                'capacity' => 2000,
                'manager_id' => $user->id,
                'is_active' => true,
            ]);

            return back()->with('success', "District Franchise '{$franchise->company_name}' registered successfully under State '{$parent->state}'.");
        });
    }

    /**
     * Edit franchise view
     */
    public function editFranchise($id)
    {
        $franchise = Franchise::with(['user', 'parentFranchise', 'districtFranchises'])->findOrFail($id);
        $stateFranchises = Franchise::where('franchise_level', 'state')->where('id', '!=', $franchise->id)->get();
        return view('admin.franchises.edit', compact('franchise', 'stateFranchises'));
    }

    /**
     * Franchise 360° Overview Page for Super Admin
     */
    public function overviewFranchise(Request $request, $id)
    {
        $franchise = Franchise::with(['user', 'parentFranchise', 'districtFranchises.user'])->findOrFail($id);

        $activeTab = $request->query('tab', 'overview');

        // Build base shipments query according to hierarchy
        if ($franchise->isState()) {
            $districtIds = $franchise->districtFranchises->pluck('id')->toArray();
            $shipmentsBase = Shipment::where(function($q) use ($franchise, $districtIds) {
                $q->where('state_franchise_id', $franchise->id);
                if (!empty($districtIds)) {
                    $q->orWhereIn('franchise_id', $districtIds);
                }
            });
        } else {
            $shipmentsBase = Shipment::where('franchise_id', $franchise->id);
        }

        // 1. High-level Overview Metrics
        $totalShipments = (clone $shipmentsBase)->count();
        $deliveredCount = (clone $shipmentsBase)->where('status', 'Delivered')->count();
        $pendingCount = (clone $shipmentsBase)->whereIn('status', ['Pending', 'Manifested', 'In Transit', 'Received', 'Out for Delivery', 'Pickup Scheduled'])->count();
        $ndrRtoCount = (clone $shipmentsBase)->where(function($q) { $q->where('status', 'NDR')->orWhere('status', 'LIKE', 'RTO%'); })->count();

        // Accurate COD metrics (Delivered COD orders only)
        $deliveredCodQuery = (clone $shipmentsBase)->where('status', 'Delivered')
            ->where(function($q) { $q->where('payment_type', 'COD')->orWhere('is_cod', 1); });

        $totalCodDeliveredCount = (clone $deliveredCodQuery)->count();
        $totalCodCollected = (clone $deliveredCodQuery)->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));
        $totalCodRemitted = (clone $deliveredCodQuery)->where('cod_remitted', 1)->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));
        $pendingCodOutstanding = (clone $deliveredCodQuery)->where(function($q) { $q->where('cod_remitted', 0)->orWhereNull('cod_remitted'); })->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));

        // Commission Ledger Metrics
        $totalCommissionEarned = FranchiseCommission::where('franchise_id', $franchise->id)
            ->where('status', 'credited')
            ->sum('amount');
        $totalCommissionPending = FranchiseCommission::where('franchise_id', $franchise->id)
            ->where('status', 'pending')
            ->sum('amount');

        $metrics = (object) [
            'total_shipments' => $totalShipments,
            'delivered' => $deliveredCount,
            'pending' => $pendingCount,
            'ndr_rto' => $ndrRtoCount,
            'cod_delivered_count' => $totalCodDeliveredCount,
            'cod_collected' => (float) $totalCodCollected,
            'cod_remitted' => (float) $totalCodRemitted,
            'pending_cod' => (float) $pendingCodOutstanding,
            'commission_earned' => (float) $totalCommissionEarned,
            'commission_pending' => (float) $totalCommissionPending,
            'wallet_balance' => (float) ($franchise->user?->wallet_balance ?? 0),
        ];

        // 2. Data for Tab 2: Orders / Shipments
        $shipments = null;
        if ($activeTab === 'orders' || $activeTab === 'overview') {
            $orderQuery = (clone $shipmentsBase)->with(['user', 'districtFranchise', 'stateFranchise']);
            if ($request->filled('search')) {
                $s = $request->search;
                $orderQuery->where(function($q) use ($s) {
                    $q->where('awb_number', 'LIKE', "%{$s}%")
                      ->orWhere('receiver_name', 'LIKE', "%{$s}%")
                      ->orWhere('receiver_phone', 'LIKE', "%{$s}%")
                      ->orWhere('delivery_city', 'LIKE', "%{$s}%");
                });
            }
            if ($request->filled('status')) {
                $orderQuery->where('status', $request->status);
            }
            $shipments = $orderQuery->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        }

        // 3. Data for Tab 3: COD Reconciliation
        $codShipments = null;
        if ($activeTab === 'cod') {
            $codShipmentQuery = (clone $deliveredCodQuery)->with('user');
            if ($request->filled('cod_status')) {
                if ($request->cod_status === 'remitted') {
                    $codShipmentQuery->where('cod_remitted', 1);
                } elseif ($request->cod_status === 'pending') {
                    $codShipmentQuery->where(function($q) { $q->where('cod_remitted', 0)->orWhereNull('cod_remitted'); });
                }
            }
            $codShipments = $codShipmentQuery->orderBy('updated_at', 'desc')->paginate(15)->withQueryString();
        }

        // 4. Data for Tab 4: Commission Ledger
        $commissions = null;
        if ($activeTab === 'commissions') {
            $commQuery = FranchiseCommission::with('shipment')
                ->where('franchise_id', $franchise->id);
            if ($request->filled('comm_type')) {
                $commQuery->where('commission_type', $request->comm_type);
            }
            $commissions = $commQuery->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        }

        // 5. Data for Tab 5: Wallet Transactions
        $transactions = null;
        if ($activeTab === 'wallet') {
            $transactions = WalletTransaction::where('user_id', $franchise->user_id)
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        }

        return view('admin.franchises.overview', compact(
            'franchise',
            'activeTab',
            'metrics',
            'shipments',
            'codShipments',
            'commissions',
            'transactions'
        ));
    }

    /**
     * Update franchise details and territory
     */
    public function updateFranchise(Request $request, $id)
    {
        $franchise = Franchise::findOrFail($id);

        $rules = [
            'company_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'address' => 'required|string',
            'status' => 'required|in:approved,pending,rejected,inactive',
            'password' => 'nullable|string|min:6',
        ];

        if ($franchise->isState()) {
            $rules['state_royalty_type'] = 'required|in:flat,per_kg,percent';
            $rules['state_royalty_rate'] = 'required|numeric|min:0';
        } else {
            $rules['parent_franchise_id'] = 'required|exists:franchises,id';
            $rules['district'] = 'required|string|max:100';
            $rules['delivery_commission_type'] = 'required|in:flat,per_kg,percent';
            $rules['delivery_commission_rate'] = 'required|numeric|min:0';
            $rules['pickup_commission_type'] = 'required|in:flat,per_kg,percent';
            $rules['pickup_commission_rate'] = 'required|numeric|min:0';
            $rules['serviceable_pincodes'] = 'required|string';
        }

        $validated = $request->validate($rules);

        // Check territory conflicts if district franchise
        if ($franchise->isDistrict()) {
            $parent = Franchise::findOrFail($validated['parent_franchise_id']);
            if (!$parent->isState()) {
                return back()->withInput()->with('error', 'Selected parent franchise must be a State franchise.');
            }

            $pincodesArray = array_values(array_filter(array_unique(array_map('trim', explode(',', $validated['serviceable_pincodes'])))));

            $conflicts = ServiceablePincode::whereIn('pincode', $pincodesArray)
                ->where('is_active', true)
                ->where('franchise_id', '!=', $franchise->id)
                ->with('franchise')
                ->get();

            if ($conflicts->isNotEmpty()) {
                $conflictDetails = $conflicts->map(fn($p) => "{$p->pincode} (" . ($p->franchise?->company_name ?? 'Other') . ")")->implode(', ');
                return back()->withInput()->with('error', "Territory Conflict: Pincode(s) are already assigned to active franchise: {$conflictDetails}.");
            }

            $validated['serviceable_pincodes'] = $pincodesArray;
            $validated['state'] = $parent->state;
        }

        DB::transaction(function () use ($franchise, $validated) {
            $franchiseData = collect($validated)->except('password')->all();
            $franchise->update($franchiseData);

            // Sync user data if linked
            if ($franchise->user_id && $user = User::find($franchise->user_id)) {
                $userUpdates = [
                    'name' => $validated['owner_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                ];
                if (!empty($validated['password'])) {
                    $userUpdates['password'] = Hash::make($validated['password']);
                }
                $user->update($userUpdates);
            }

            // Sync serviceable pincodes if district
            if ($franchise->isDistrict()) {
                $pincodes = $validated['serviceable_pincodes'];
                // Deactivate old pins not in new list
                ServiceablePincode::where('franchise_id', $franchise->id)
                    ->whereNotIn('pincode', $pincodes)
                    ->delete();

                foreach ($pincodes as $pin) {
                    ServiceablePincode::updateOrCreate(
                        ['pincode' => $pin],
                        [
                            'franchise_id' => $franchise->id,
                            'city' => $validated['city'],
                            'state' => $franchise->state,
                            'is_active' => $franchise->status === 'approved',
                        ]
                    );
                }
            }
        });

        return redirect()->route('admin.hubs.index')->with('success', "Franchise '{$franchise->company_name}' updated successfully.");
    }

    /**
     * Toggle active/inactive status of franchise
     */
    public function toggleFranchise($id)
    {
        $franchise = Franchise::findOrFail($id);
        $newStatus = $franchise->status === 'approved' ? 'inactive' : 'approved';
        $franchise->status = $newStatus;
        $franchise->save();

        // Also toggle active state on associated pincodes
        ServiceablePincode::where('franchise_id', $franchise->id)->update([
            'is_active' => ($newStatus === 'approved')
        ]);

        return back()->with('success', "Franchise '{$franchise->company_name}' marked as {$newStatus}.");
    }

    public function approveFranchise(Request $request, $id)
    {
        $franchise = Franchise::findOrFail($id);

        $validated = $request->validate([
            'franchise_level' => 'required|in:state,district',
            'parent_franchise_id' => 'nullable|required_if:franchise_level,district|exists:franchises,id',
            'district' => 'nullable|required_if:franchise_level,district|string|max:100',
            'serviceable_pincodes' => 'nullable|string',
            'delivery_commission_type' => 'nullable|in:flat,per_kg,percent',
            'delivery_commission_rate' => 'nullable|numeric|min:0',
            'pickup_commission_type' => 'nullable|in:flat,per_kg,percent',
            'pickup_commission_rate' => 'nullable|numeric|min:0',
            'state_royalty_type' => 'nullable|in:flat,per_kg,percent',
            'state_royalty_rate' => 'nullable|numeric|min:0',
        ]);

        if ($validated['franchise_level'] === 'district' && !empty($validated['parent_franchise_id'])) {
            $parent = Franchise::findOrFail($validated['parent_franchise_id']);
            if (!$parent->isState()) {
                return back()->with('error', 'Selected parent must be a State-level franchise.');
            }
        }

        DB::transaction(function () use ($franchise, $validated) {
            $franchise->status = 'approved';
            $franchise->franchise_level = $validated['franchise_level'];
            $franchise->parent_franchise_id = $validated['franchise_level'] === 'state' ? null : ($validated['parent_franchise_id'] ?? null);
            $franchise->district = $validated['district'] ?? $franchise->district;

            if ($validated['franchise_level'] === 'state') {
                $franchise->state_royalty_type = $validated['state_royalty_type'] ?? 'percent';
                $franchise->state_royalty_rate = $validated['state_royalty_rate'] ?? 0;
            } else {
                if (!empty($validated['serviceable_pincodes'])) {
                    $pincodesArray = array_values(array_filter(array_unique(array_map('trim', explode(',', $validated['serviceable_pincodes'])))));
                    $franchise->serviceable_pincodes = $pincodesArray;
                }

                $franchise->delivery_commission_type = $validated['delivery_commission_type'] ?? 'flat';
                $franchise->delivery_commission_rate = $validated['delivery_commission_rate'] ?? 0;
                $franchise->pickup_commission_type = $validated['pickup_commission_type'] ?? 'flat';
                $franchise->pickup_commission_rate = $validated['pickup_commission_rate'] ?? 0;

                // Sync serviceable pincodes
                $pins = is_array($franchise->serviceable_pincodes) ? $franchise->serviceable_pincodes : json_decode($franchise->serviceable_pincodes, true);
                if (is_array($pins)) {
                    foreach ($pins as $pin) {
                        ServiceablePincode::updateOrCreate(
                            ['pincode' => $pin],
                            [
                                'franchise_id' => $franchise->id,
                                'city' => $franchise->city,
                                'state' => $franchise->state,
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }

            $franchise->save();

            $user = User::find($franchise->user_id);
            if ($user) {
                $user->status = 'active';
                $user->save();
            }
        });

        return back()->with('success', "Franchise '{$franchise->company_name}' approved successfully as " . ucfirst($franchise->franchise_level) . " Franchise.");
    }

    public function rejectFranchise($id)
    {
        $franchise = Franchise::findOrFail($id);
        $franchise->status = 'rejected';
        $franchise->save();

        $user = User::find($franchise->user_id);
        if ($user) {
            $user->status = 'rejected';
            $user->save();
        }

        // Deactivate serviceable pincodes if any
        ServiceablePincode::where('franchise_id', $franchise->id)->update(['is_active' => false]);

        return back()->with('success', 'Franchise application rejected.');
    }

    public function edit($id)
    {
        $hub = Hub::findOrFail($id);
        $managers = User::where('role', 'franchise')->get();
        return view('admin.hubs.edit', compact('hub', 'managers'));
    }

    public function update(Request $request, $id)
    {
        $hub = Hub::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'capacity' => 'required|numeric|min:1',
            'manager_id' => 'nullable|exists:users,id'
        ]);

        $hub->update($validated);

        return redirect()->route('admin.hubs.index')->with('success', 'Hub updated successfully.');
    }

    public function storeManager(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8'
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'franchise',
            'status' => 'active'
        ]);

        return back()->with('success', 'Franchise Manager created successfully! You can now assign them to a hub or franchise.');
    }

    public function creditFranchiseWallet(Request $request, $id)
    {
        $franchise = Franchise::findOrFail($id);
        
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000',
            'remarks' => 'nullable|string|max:255'
        ]);

        $amount = (float)$request->amount;
        $admin = \Illuminate\Support\Facades\Auth::user();
        $adminName = $admin ? $admin->name : 'Admin';
        $remarks = $request->input('remarks') ?: 'Admin Manual Wallet Credit';
        $auditDescription = "{$remarks} (Authorized by {$adminName})";
        $reference = 'ADMIN_CREDIT_' . strtoupper(\Illuminate\Support\Str::random(8));

        app(\App\Services\WalletService::class)->credit(
            $franchise->user_id,
            $amount,
            $auditDescription,
            $reference
        );

        return back()->with('success', 'Wallet successfully credited with ₹' . number_format($amount, 2));
    }
}
