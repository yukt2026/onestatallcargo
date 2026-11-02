<?php
namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerNdrController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'action_required');

        $query = Shipment::where('user_id', Auth::id());

        if ($tab === 'action_required') {
            $query->where('status', 'NDR')->whereNull('ndr_action');
        } elseif ($tab === 'action_taken') {
            $query->where('status', 'NDR')->whereNotNull('ndr_action');
        } elseif ($tab === 'delivered') {
            $query->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->whereNotNull('ndr_action'); // Was NDR but now delivered
        } elseif ($tab === 'rto') {
            $query->where(function($q) {
                $q->where('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%');
            });
        } elseif ($tab === 'all') {
            $query->where(function ($q) {
                $q->whereIn('status', ['NDR', 'ndr', 'Action Required', 'Undelivered'])
                  ->orWhere('status', 'LIKE', 'RTO%')
                  ->orWhere('status', 'LIKE', 'rto%');
            });
        }

        if ($request->filled('search')) {
            $query->where('awb_number', 'like', '%' . $request->search . '%');
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

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('updated_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        } elseif ($request->filled('start_date')) {
            $query->where('updated_at', '>=', $request->start_date . ' 00:00:00');
        } elseif ($request->filled('end_date')) {
            $query->where('updated_at', '<=', $request->end_date . ' 23:59:59');
        } elseif ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->filled('reason')) {
            $query->where('ndr_reason', $request->reason);
        }

        $ndrShipments = $query->orderBy('updated_at', 'desc')->paginate(15)->withQueryString();
            
        return view('seller.ndr', compact('ndrShipments', 'tab'));
    }

    public function action(Request $request, $awb)
    {
        $validated = $request->validate([
            'ndr_action' => 'required|string|in:Re-attempt,RTO,Hold'
        ]);

        $shipment = Shipment::where('awb_number', $awb)->where('user_id', Auth::id())->firstOrFail();
        
        // Only update the ndr_action, leave status as NDR so it moves to "Action Taken" tab
        $shipment->ndr_action = $validated['ndr_action'];
        $shipment->save();
        
        \App\Models\ShipmentEvent::create([
            'shipment_id' => $shipment->id,
            'status' => 'NDR Action: ' . $validated['ndr_action'],
            'remarks' => 'Seller submitted NDR action: ' . $validated['ndr_action']
        ]);

        return back()->with('success', 'NDR Action (' . $validated['ndr_action'] . ') submitted for ' . $awb);
    }
}
