<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\Franchise;
use App\Models\Rider;
use App\Models\Hub;

class HubNdrController extends Controller
{
    private function getAuthScope()
    {
        $user = Auth::user();
        $franchise = Franchise::where('user_id', $user->id)->first();
        $hubs = Hub::where('manager_id', $user->id)->get();

        if (!$franchise && $hubs->isEmpty()) abort(403, 'Unauthorized');

        return [
            'franchise_id' => $franchise ? $franchise->id : null,
            'hubs' => $hubs,
            'city' => $franchise ? $franchise->city : ($hubs->first() ? $hubs->first()->city : 'Hub')
        ];
    }

    public function index(Request $request)
    {
        $scope = $this->getAuthScope();
        
        $shipmentsQuery = Shipment::whereIn('status', ['NDR', 'ndr', 'Action Required', 'Undelivered']);
        if ($scope['franchise_id']) {
            $franchise = \App\Models\Franchise::find($scope['franchise_id']);
            $pincodes = $franchise ? (is_array($franchise->serviceable_pincodes) ? $franchise->serviceable_pincodes : (json_decode($franchise->serviceable_pincodes, true) ?? [])) : [];
            if (!is_array($pincodes)) {
                $pincodes = $pincodes ? [$pincodes] : [];
            }
            $shipmentsQuery->where(function($q) use ($scope, $pincodes) {
                $q->where('franchise_id', $scope['franchise_id'])
                  ->orWhere('user_id', Auth::id());
                if (!empty($pincodes)) {
                    $q->orWhereIn('pickup_pincode', $pincodes)
                      ->orWhereIn('delivery_pincode', $pincodes);
                }
            });
        } else {
            $shipmentsQuery->whereNull('franchise_id')->where(function($q) use ($scope) {
                $q->where('pickup_city', $scope['city'])->orWhere('delivery_city', $scope['city']);
            });
        }

        // Quick date preset resolution
        if ($request->filled('quick_date') && (!$request->filled('start_date') || !$request->filled('end_date'))) {
            $today = now()->toDateString();
            if ($request->quick_date === 'today') {
                $request->merge(['start_date' => $today, 'end_date' => $today]);
            } elseif ($request->quick_date === 'yesterday') {
                $yesterday = now()->subDay()->toDateString();
                $request->merge(['start_date' => $yesterday, 'end_date' => $yesterday]);
            } elseif ($request->quick_date === '7days') {
                $request->merge(['start_date' => now()->subDays(6)->toDateString(), 'end_date' => $today]);
            } elseif ($request->quick_date === '30days') {
                $request->merge(['start_date' => now()->subDays(29)->toDateString(), 'end_date' => $today]);
            } elseif ($request->quick_date === 'this_month') {
                $request->merge(['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => $today]);
            }
        }

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $shipmentsQuery->whereBetween('updated_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        } elseif ($request->filled('start_date')) {
            $shipmentsQuery->where('updated_at', '>=', $request->start_date . ' 00:00:00');
        } elseif ($request->filled('end_date')) {
            $shipmentsQuery->where('updated_at', '<=', $request->end_date . ' 23:59:59');
        }

        // Reason filter
        if ($request->filled('reason')) {
            $shipmentsQuery->where('ndr_reason', $request->reason);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $shipmentsQuery->where(function($q) use ($search) {
                $q->where('awb_number', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%");
            });
        }

        $shipments = $shipmentsQuery->orderBy('updated_at', 'desc')->paginate(15)->withQueryString();
            
        $ridersQuery = Rider::with('user')->where('is_active', true);
        if ($scope['franchise_id']) {
            $ridersQuery->where('franchise_id', $scope['franchise_id']);
        } else {
            $ridersQuery->whereNull('franchise_id')->whereIn('hub_id', $scope['hubs']->pluck('id'));
        }
        $riders = $ridersQuery->get();

        return view('hub.ndr.index', compact('shipments', 'riders'));
    }

    public function action(Request $request, $id)
    {
        $scope = $this->getAuthScope();
        
        $validated = $request->validate([
            'action' => 'required|in:reattempt,rto',
            'rider_id' => 'nullable|exists:riders,id',
            'remarks' => 'nullable|string|max:500'
        ]);

        return DB::transaction(function () use ($validated, $id, $scope) {
            $shipment = Shipment::lockForUpdate()->findOrFail($id);
            
            if ($scope['franchise_id']) {
                $franchise = \App\Models\Franchise::find($scope['franchise_id']);
                $pincodes = $franchise ? (is_array($franchise->serviceable_pincodes) ? $franchise->serviceable_pincodes : (json_decode($franchise->serviceable_pincodes, true) ?? [])) : [];
                if (!is_array($pincodes)) $pincodes = $pincodes ? [$pincodes] : [];
                $hasPincodeMatch = in_array($shipment->pickup_pincode, $pincodes) || in_array($shipment->delivery_pincode, $pincodes);
                if ($shipment->franchise_id !== $scope['franchise_id'] && $shipment->user_id !== Auth::id() && !$hasPincodeMatch) {
                    abort(403, 'Unauthorized');
                }
            } else {
                if ($shipment->pickup_city !== $scope['city'] && $shipment->delivery_city !== $scope['city']) {
                    abort(403, 'Unauthorized');
                }
            }

            if ($shipment->status !== 'NDR') {
                return back()->with('error', 'Shipment is no longer in NDR status.');
            }

            if ($validated['action'] === 'reattempt') {
                if (!$validated['rider_id']) return back()->with('error', 'Rider is required for reattempt.');
                
                $rider = Rider::with('user')->findOrFail($validated['rider_id']);
                if ($scope['franchise_id']) {
                    if ($rider->franchise_id !== $scope['franchise_id']) abort(403);
                } else {
                    if ($rider->franchise_id !== null || !in_array($rider->hub_id, $scope['hubs']->pluck('id')->toArray())) abort(403);
                }
                
                $shipment->status = 'Out for Delivery';
                $shipment->rider_id = $rider->user_id;
                $shipment->save();

                ShipmentEvent::create([
                    'shipment_id' => $shipment->id,
                    'status' => 'Out for Delivery',
                    'location' => $scope['city'],
                    'remarks' => 'NDR Reattempt Assigned to ' . $rider->user->name . '. ' . ($validated['remarks'] ?? '')
                ]);

                return back()->with('success', 'Shipment assigned for reattempt.');
            } else {
                $shipment->status = 'RTO';
                $shipment->rider_id = null;
                $shipment->save();

                ShipmentEvent::create([
                    'shipment_id' => $shipment->id,
                    'status' => 'RTO',
                    'location' => $scope['city'],
                    'remarks' => 'Marked as Return To Origin (RTO). ' . ($validated['remarks'] ?? '')
                ]);

                return back()->with('success', 'Shipment marked as RTO.');
            }
        });
    }
}
