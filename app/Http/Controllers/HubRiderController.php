<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Rider;
use App\Models\Hub;
use App\Models\Franchise;
use App\Models\Shipment;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;

class HubRiderController extends Controller
{
    private function getAuthScope()
    {
        $user = Auth::user();
        
        $franchise = Franchise::where('user_id', $user->id)->first();
        if ($franchise && $franchise->status !== 'approved') {
            abort(403, 'Your franchise application is pending Admin approval.');
        }

        $hubs = Hub::where('is_active', true)
            ->where(function($q) use ($user) {
                if ($user->isAdmin()) {
                    // Admins see all active hubs
                } else {
                    $q->where('manager_id', $user->id);
                }
            })->get();

        $isAuthorized = $user->isAdmin()
            || in_array($user->role, ['super_admin', 'admin', 'franchise'])
            || $franchise
            || !$hubs->isEmpty();

        if (!$isAuthorized) {
            abort(403, 'You are not a Franchise owner or Hub Manager.');
        }

        return [
            'user' => $user,
            'franchise_id' => $franchise ? $franchise->id : null,
            'hubs' => $hubs,
        ];
    }

        public function index()
    {
        $hubManagerId = Auth::id();
        $hub = \App\Models\Hub::where('manager_id', $hubManagerId)->first();
        if(!$hub) {
            $franchise = Franchise::where('user_id', $hubManagerId)->first();
            if ($franchise) {
                $pincode = '110001';
                if (is_array($franchise->serviceable_pincodes) && count($franchise->serviceable_pincodes) > 0) {
                    $pincode = $franchise->serviceable_pincodes[0];
                }
                $hub = \App\Models\Hub::firstOrCreate(
                    ['manager_id' => $hubManagerId],
                    [
                        'name' => $franchise->company_name ?? 'Franchise Hub',
                        'hub_code' => 'HUB-' . $franchise->id,
                        'city' => $franchise->city ?? 'City',
                        'pincode' => $pincode,
                        'is_active' => true,
                    ]
                );
            }
        }
        if(!$hub) {
            return redirect()->route('hub.dashboard')->with('error', 'No Hub Assigned.');
        }

        $riders = Rider::where('hub_id', $hub->id)
                      ->with(['user'])
                      ->orderBy('created_at', 'desc')
                      ->paginate(15);
                      
        // Append live performance stats to each rider
        foreach($riders as $rider) {
            if(!$rider->user) continue;
            
            $todayShipments = \App\Models\Shipment::where('rider_id', $rider->user->id)
                ->where('status', 'Delivered')
                ->whereDate('updated_at', today())
                ->get();
                
            $rider->today_deliveries = $todayShipments->count();
            
            $rider->today_cash = $todayShipments->where('is_cod', 1)->filter(function($s) {
                return empty($s->payment_type) || $s->payment_type === 'Cash';
            })->sum(function($s) {
                return $s->cod_amount > 0 ? $s->cod_amount : $s->invoice_value;
            });
            
            $rider->today_upi = $todayShipments->where('payment_type', 'UPI')->sum(function($s) {
                return $s->cod_amount > 0 ? $s->cod_amount : $s->invoice_value;
            });
        }
        
        $hubs = \App\Models\Hub::where('manager_id', $hubManagerId)->get();
        if ($hubs->isEmpty() && $hub) {
            $hubs = collect([$hub]);
        }

        return view('hub.fleet.index', compact('riders', 'hub', 'hubs'));
    }

    public function store(Request $request)
    {
        $scope = $this->getAuthScope();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:rider,pickup_rider,delivery_rider',
            'hub_id' => 'nullable|exists:hubs,id',
            'vehicle_type' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:100',
        ]);

        if ($validated['hub_id']) {
            $hub = Hub::find($validated['hub_id']);
            if ($hub->manager_id !== $scope['user']->id) {
                return back()->with('error', 'UNAUTHORIZED: You can only assign riders to hubs you manage.');
            }
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role']
        ]);

        Rider::create([
            'user_id' => $user->id,
            'franchise_id' => $scope['franchise_id'],
            'hub_id' => $validated['hub_id'] ?? null,
            'vehicle_type' => $validated['vehicle_type'] ?? null,
            'vehicle_number' => $validated['vehicle_number'] ?? null,
            'status' => 'active',
            'is_active' => true
        ]);

        return back()->with('success', 'Rider created successfully.');
    }

    public function update(Request $request, $id)
    {
        $scope = $this->getAuthScope();
        
        $rider = Rider::with('user')->findOrFail($id);
        
        if ($scope['franchise_id']) {
            if ($rider->franchise_id !== $scope['franchise_id']) abort(403, 'UNAUTHORIZED');
        } else {
            if ($rider->franchise_id !== null || !in_array($rider->hub_id, $scope['hubs']->pluck('id')->toArray())) {
                abort(403, 'UNAUTHORIZED');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', Rule::unique('users')->ignore($rider->user_id)],
            'password' => 'nullable|string|min:6',
            'hub_id' => 'nullable|exists:hubs,id',
            'vehicle_type' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:100',
            'status' => 'required|in:active,suspended',
        ]);

        if ($validated['hub_id']) {
            $hub = Hub::find($validated['hub_id']);
            if ($hub->manager_id !== $scope['user']->id) {
                return back()->with('error', 'UNAUTHORIZED: You can only assign riders to hubs you manage.');
            }
        }

        $userData = [
            'name' => $validated['name'],
            'phone' => $validated['phone']
        ];
        
        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }
        
        $rider->user->update($userData);

        $rider->update([
            'hub_id' => $validated['hub_id'],
            'vehicle_type' => $validated['vehicle_type'] ?? null,
            'vehicle_number' => $validated['vehicle_number'] ?? null,
            'status' => $validated['status'],
            'is_active' => ($validated['status'] === 'active')
        ]);

        return back()->with('success', 'Rider updated successfully.');
    }

    public function destroy($id)
    {
        $scope = $this->getAuthScope();
        $rider = Rider::with("user")->findOrFail($id);
        
        if ($scope["franchise_id"]) {
            if ($rider->franchise_id !== $scope["franchise_id"]) abort(403, "UNAUTHORIZED");
        } else {
            if ($rider->franchise_id !== null || !in_array($rider->hub_id, $scope["hubs"]->pluck("id")->toArray())) {
                abort(403, "UNAUTHORIZED");
            }
        }

        // Delete the user record, which cascades to delete the rider record
        $rider->user->delete();

        return back()->with("success", "Rider deleted successfully.");
    }

    public function report(Request $request, $id)
    {
        $scope = $this->getAuthScope();
        $rider = Rider::with(['user', 'hub', 'franchise'])->findOrFail($id);
        $this->authorizeRiderAccess($rider, $scope);

        $selectedDate = $this->resolveDate($request->query('date'));
        $data = $this->getDailyRunData($rider, $selectedDate);

        return view('hub.fleet.report', array_merge($data, [
            'rider' => $rider,
            'selectedDate' => $selectedDate,
        ]));
    }

    public function printRunSheet(Request $request, $id)
    {
        $scope = $this->getAuthScope();
        $rider = Rider::with(['user', 'hub', 'franchise'])->findOrFail($id);
        $this->authorizeRiderAccess($rider, $scope);

        $selectedDate = $this->resolveDate($request->query('date'));
        $data = $this->getDailyRunData($rider, $selectedDate);

        $hubName = $rider->hub ? $rider->hub->name : ($rider->franchise ? ($rider->franchise->company_name ?? $rider->franchise->name ?? 'Franchise Hub') : 'Central Logistics Hub');
        $hubCity = $rider->hub ? $rider->hub->city : ($rider->franchise ? $rider->franchise->city : '');
        $hubState = $rider->hub ? $rider->hub->state : ($rider->franchise ? $rider->franchise->state : '');
        $runSheetNumber = 'DRS-' . Carbon::parse($selectedDate)->format('Ymd') . '-' . str_pad($rider->id, 4, '0', STR_PAD_LEFT);

        return view('hub.fleet.runsheet', array_merge($data, [
            'rider'          => $rider,
            'selectedDate'   => $selectedDate,
            'hubName'        => $hubName,
            'hubCity'        => $hubCity,
            'hubState'       => $hubState,
            'runSheetNumber' => $runSheetNumber,
            'authScope'      => $scope,
        ]));
    }

    public function exportRunSheetCsv(Request $request, $id)
    {
        $scope = $this->getAuthScope();
        $rider = Rider::with(['user', 'hub', 'franchise'])->findOrFail($id);
        $this->authorizeRiderAccess($rider, $scope);

        $selectedDate = $this->resolveDate($request->query('date'));
        $data = $this->getDailyRunData($rider, $selectedDate);

        $cleanDate = Carbon::parse($selectedDate)->format('Ymd');
        $filename = "DRS_{$cleanDate}_Rider_{$rider->id}_{$rider->user->name}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($rider, $selectedDate, $data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Run Sheet Header Info
            fputcsv($file, ['DAILY RUN SHEET & HANDOVER MANIFEST (DRS)']);
            fputcsv($file, ['Rider Name', $rider->user->name, 'Phone', $rider->user->phone]);
            fputcsv($file, ['Vehicle Type', $rider->vehicle_type ?? 'N/A', 'Vehicle No', $rider->vehicle_number ?? 'N/A']);
            fputcsv($file, ['Date', $selectedDate, 'Generated At', now()->format('d-m-Y H:i:s')]);
            fputcsv($file, []);

            // Summary Metrics
            fputcsv($file, ['SUMMARY METRICS']);
            fputcsv($file, ['Total Parcels Given for Delivery', $data['totalGiven']]);
            fputcsv($file, ['Delivered Successfully', $data['totalDeliveries']]);
            fputcsv($file, ['Pending Out for Delivery', $data['totalPendingDeliveries']]);
            fputcsv($file, ['Undelivered / Returned to Hub', $data['totalReturnedToHub']]);
            fputcsv($file, ['Pickups Collected & Received at Hub', $data['totalPickupsReceived']]);
            fputcsv($file, ['Total Physical Parcels Received at Hub', $data['totalParcelsReceivedAtHub']]);
            fputcsv($file, ['Hard Cash Collected (Hub Deposit)', number_format($data['totalCash'], 2, '.', '')]);
            fputcsv($file, ['UPI Digital Collected', number_format($data['totalUpi'], 2, '.', '')]);
            fputcsv($file, []);

            // SECTION 1: Parcels Given for Delivery
            fputcsv($file, ['SECTION 1: PARCELS GIVEN FOR DELIVERY (DISPATCH / OUT FOR DELIVERY)']);
            fputcsv($file, [
                'Sl No', 'AWB Number', 'Order ID', 'Customer Name', 'Customer Phone',
                'Delivery Address', 'City', 'Pincode', 'Payment Mode', 'COD Amount (INR)',
                'Current Status', 'Payment Method', 'Updated Time'
            ]);

            $sl = 1;
            foreach ($data['givenForDelivery'] as $s) {
                $codAmt = $s->is_cod ? ($s->cod_amount > 0 ? $s->cod_amount : $s->invoice_value) : 0;
                fputcsv($file, [
                    $sl++,
                    $s->awb_number,
                    $s->order_id ?? 'N/A',
                    $s->receiver_name,
                    $s->receiver_phone,
                    $s->delivery_address,
                    $s->delivery_city,
                    $s->delivery_pincode,
                    $s->is_cod ? 'COD' : 'Prepaid',
                    number_format((float) $codAmt, 2, '.', ''),
                    $s->status,
                    $s->payment_type ?? 'N/A',
                    $s->updated_at ? $s->updated_at->format('h:i A') : '',
                ]);
            }
            fputcsv($file, []);

            // SECTION 2: Pickups Received at Hub
            fputcsv($file, ['SECTION 2: PICKUPS RECEIVED AT HUB (INWARD FROM SELLERS)']);
            fputcsv($file, [
                'Sl No', 'AWB Number', 'Sender / Store', 'Pickup Address',
                'Destination City', 'Weight (KG)', 'Status', 'Received Time'
            ]);

            $sl = 1;
            foreach ($data['receivedPickups'] as $s) {
                fputcsv($file, [
                    $sl++,
                    $s->awb_number,
                    $s->user ? ($s->user->company_name ?: $s->user->name) : 'Sender',
                    $s->pickup_address ?? 'Registered Pickup',
                    $s->delivery_city,
                    $s->weight_kg,
                    $s->status,
                    $s->updated_at ? $s->updated_at->format('h:i A') : '',
                ]);
            }
            fputcsv($file, []);

            // SECTION 3: Undelivered / NDR Parcels Returned to Hub
            fputcsv($file, ['SECTION 3: UNDELIVERED / NDR PARCELS RETURNED TO HUB']);
            fputcsv($file, [
                'Sl No', 'AWB Number', 'Customer Name', 'City',
                'NDR Reason', 'Return Custody Status', 'Time'
            ]);

            $sl = 1;
            foreach ($data['returnedToHub'] as $s) {
                fputcsv($file, [
                    $sl++,
                    $s->awb_number,
                    $s->receiver_name,
                    $s->delivery_city,
                    $s->ndr_reason ?? 'Failed Delivery Attempt',
                    'Returned to Hub Inventory',
                    $s->updated_at ? $s->updated_at->format('h:i A') : '',
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    private function getDailyRunData(Rider $rider, string $selectedDate): array
    {
        $userId = $rider->user->id;
        $date = Carbon::parse($selectedDate)->toDateString();
        $isToday = ($date === today()->toDateString());

        // 1. Parcels Given For Delivery (दिए गए पार्सल)
        $givenForDelivery = Shipment::with('user')
            ->where('rider_id', $userId)
            ->where(function ($q) use ($date, $isToday) {
                $q->whereDate('updated_at', $date);
                if ($isToday) {
                    $q->orWhere(function ($sub) use ($date) {
                        $sub->where('status', 'Out for Delivery')
                            ->whereDate('created_at', '<=', $date);
                    });
                }
            })
            ->whereIn('status', ['Out for Delivery', 'Delivered', 'NDR', 'RTO Initiated', 'RTO Delivered'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $deliveredShipments = $givenForDelivery->where('status', 'Delivered');
        $outForDeliveryShipments = $givenForDelivery->where('status', 'Out for Delivery');
        $returnedToHub = $givenForDelivery->whereIn('status', ['NDR', 'RTO Initiated', 'RTO Delivered']);

        // 2. Pickups Collected by Rider & Handed over to Hub (रिसीव किए गए पिकअप)
        $receivedPickups = Shipment::with('user')
            ->where('rider_id', $userId)
            ->whereIn('status', ['In Transit', 'Received', 'Manifested'])
            ->whereDate('updated_at', $date)
            ->orderBy('updated_at', 'desc')
            ->get();

        // 3. Cash Reconciliation
        $totalCash = $deliveredShipments->where('is_cod', 1)->filter(function ($s) {
            return empty($s->payment_type) || strcasecmp($s->payment_type, 'Cash') === 0;
        })->sum(function ($s) {
            return $s->cod_amount > 0 ? $s->cod_amount : $s->invoice_value;
        });

        $totalUpi = $deliveredShipments->where('payment_type', 'UPI')->sum(function ($s) {
            return $s->cod_amount > 0 ? $s->cod_amount : $s->invoice_value;
        });

        $totalDeliveries = $deliveredShipments->count();
        $totalPendingDeliveries = $outForDeliveryShipments->count();
        $totalReturnedToHub = $returnedToHub->count();
        $totalPickupsReceived = $receivedPickups->count();
        $totalParcelsReceivedAtHub = $totalPickupsReceived + $totalReturnedToHub;
        $totalGiven = $givenForDelivery->count();
        $totalCodCollected = $totalCash + $totalUpi;

        // Keep backwards compatible $shipments variable for existing delivered view
        $shipments = $deliveredShipments;

        return compact(
            'givenForDelivery',
            'deliveredShipments',
            'outForDeliveryShipments',
            'returnedToHub',
            'receivedPickups',
            'shipments',
            'totalGiven',
            'totalDeliveries',
            'totalPendingDeliveries',
            'totalReturnedToHub',
            'totalPickupsReceived',
            'totalParcelsReceivedAtHub',
            'totalCash',
            'totalUpi',
            'totalCodCollected'
        );
    }

    private function resolveDate(?string $date): string
    {
        if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        return today()->toDateString();
    }

    private function authorizeRiderAccess(Rider $rider, array $scope): void
    {
        $user = $scope['user'];
        if ($user->isAdmin()) {
            return;
        }

        if ($scope['franchise_id']) {
            $allowedFranchiseIds = [$scope['franchise_id']];
            $currentFranchise = Franchise::find($scope['franchise_id']);
            if ($currentFranchise && ($currentFranchise->franchise_level === 'state' || ($currentFranchise->type ?? null) === 'state')) {
                $subIds = Franchise::where('parent_franchise_id', $currentFranchise->id)->pluck('id')->toArray();
                $allowedFranchiseIds = array_merge($allowedFranchiseIds, $subIds);
            }

            if (!in_array($rider->franchise_id, $allowedFranchiseIds)) {
                abort(403, 'Unauthorized access to rider.');
            }
        } else {
            $allowedHubIds = $scope['hubs']->pluck('id')->toArray();
            if ($rider->franchise_id !== null || !in_array($rider->hub_id, $allowedHubIds)) {
                abort(403, 'Unauthorized access to rider.');
            }
        }
    }
}