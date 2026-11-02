<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipment;

class AdminNDRController extends Controller 
{
    public function index() 
    {
        $shipments = Shipment::where(function($q) {
                        $q->whereIn('status', ['NDR', 'ndr', 'Action Required', 'Undelivered'])
                          ->orWhere('status', 'LIKE', 'RTO%')
                          ->orWhere('status', 'LIKE', 'rto%');
                    })
                    ->with('user')
                    ->orderBy('updated_at', 'desc')
                    ->paginate(15)->withQueryString();
        return view('admin.ndr.index', compact('shipments'));
    }

    public function action(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_action' => 'required|in:reattempt,rto,rto_delivered'
        ]);

        $shipment = Shipment::findOrFail($id);

        if ($validated['admin_action'] === 'reattempt') {
            $shipment->status = 'Out for Delivery';
            $shipment->ndr_action = null; // Clear the pending seller action
            $shipment->save();
            
            \App\Models\ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => 'Out for Delivery',
                'remarks' => 'Admin pushed shipment for Re-attempt delivery.'
            ]);
            return back()->with('success', 'Shipment ' . $shipment->awb_number . ' pushed back to Out for Delivery.');
        }

        if ($validated['admin_action'] === 'rto') {
            $shipment->status = 'RTO Initiated';
            $shipment->save();
            
            \App\Models\ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => 'RTO Initiated',
                'remarks' => 'Return to Origin (RTO) initiated by Admin.'
            ]);
            return back()->with('success', 'RTO officially initiated for ' . $shipment->awb_number . '.');
        }
        
        if ($validated['admin_action'] === 'rto_delivered') {
            $shipment->status = 'RTO Delivered';
            $shipment->save();
            
            \App\Models\ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => 'RTO Delivered',
                'remarks' => 'Shipment successfully returned and delivered to Seller.'
            ]);
            return back()->with('success', 'Shipment ' . $shipment->awb_number . ' successfully returned to Seller.');
        }
        
        return back();
    }
}
