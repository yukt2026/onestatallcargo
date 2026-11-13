<?php
namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function index(Request $request)
    {
        $awb = $request->query('awb') ?? $request->query('awb_number');
        if ($awb) {
            $searchAwb = strtoupper(trim($awb));
            $numericPart = preg_replace('/^AWB-?/i', '', $searchAwb);

            $shipment = Shipment::where('awb_number', $searchAwb)
                ->orWhere('awb_number', 'AWB-' . $numericPart)
                ->orWhere('awb_number', $numericPart)
                ->orWhere('courier_awb', $searchAwb)
                ->orWhere('courier_awb', $numericPart)
                ->orWhere('order_id', $searchAwb)
                ->with(['events' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }])->first();

            if ($shipment) {
                return view('track', compact('shipment'));
            }
        }

        return view('track');
    }

    public function track(Request $request)
    {
        $request->validate(['awb_number' => 'required|string']);
        
        $searchAwb = strtoupper(trim($request->awb_number));
        $numericPart = preg_replace('/^AWB-?/i', '', $searchAwb);

        $shipment = Shipment::where('awb_number', $searchAwb)
            ->orWhere('awb_number', 'AWB-' . $numericPart)
            ->orWhere('awb_number', $numericPart)
            ->orWhere('courier_awb', $searchAwb)
            ->orWhere('courier_awb', $numericPart)
            ->orWhere('order_id', $searchAwb)
            ->with(['events' => function($q) {
                $q->orderBy('created_at', 'desc');
            }])->first();

        if (!$shipment) {
            return back()->with('error', 'No shipment found with that AWB Number.');
        }

        return view('track', compact('shipment'));
    }
}
