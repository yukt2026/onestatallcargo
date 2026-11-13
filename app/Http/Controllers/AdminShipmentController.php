<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class AdminShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($sub) use ($search) {
                $sub->where('awb_number', 'LIKE', "%{$search}%")
                    ->orWhere('order_id', 'LIKE', "%{$search}%")
                    ->orWhere('receiver_name', 'LIKE', "%{$search}%")
                    ->orWhere('receiver_phone', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('company_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Quick Date preset resolution
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
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        } elseif ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date . ' 00:00:00');
        } elseif ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        // Payment mode filter
        if ($request->filled('payment_mode')) {
            if ($request->payment_mode === 'cod') {
                $query->where('is_cod', 1);
            } elseif ($request->payment_mode === 'prepaid') {
                $query->where('is_cod', 0);
            }
        }

        // Courier partner filter
        if ($request->filled('courier') && $request->courier !== 'all') {
            $query->where('courier_partner', $request->courier);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $status = strtolower(trim($request->status));
            if ($status === 'rto' || str_starts_with($status, 'rto')) {
                $query->where(function($q) {
                    $q->where('status', 'LIKE', 'RTO%')
                      ->orWhere('status', 'LIKE', 'rto%');
                });
            } elseif ($status === 'pickup' || str_starts_with($status, 'pickup')) {
                $query->where(function($q) {
                    $q->where('status', 'LIKE', 'pickup%')
                      ->orWhere('status', 'LIKE', 'Pickup%');
                });
            } elseif ($status === 'transit' || in_array($status, ['in transit', 'in_transit'])) {
                $query->whereIn('status', ['in_transit', 'In Transit', 'transit', 'Transit', 'In-Transit']);
            } elseif (in_array($status, ['out for delivery', 'out_for_delivery', 'ofd'])) {
                $query->whereIn('status', ['out_for_delivery', 'Out for Delivery', 'ofd', 'OFD']);
            } elseif ($status === 'delivered') {
                $query->whereIn('status', ['delivered', 'Delivered', 'DELIVERED']);
            } elseif ($status === 'cancelled' || str_starts_with($status, 'cancel')) {
                $query->where(function($q) {
                    $q->where('status', 'LIKE', 'cancel%')
                      ->orWhere('status', 'LIKE', 'Cancel%');
                });
            } elseif (in_array($status, ['ndr', 'action_required', 'undelivered'])) {
                $query->whereIn('status', ['ndr', 'NDR', 'action_required', 'undelivered', 'Undelivered']);
            } elseif (in_array($status, ['new', 'manifested', 'booked', 'pending'])) {
                $query->whereIn('status', ['new', 'New', 'Manifested', 'manifested', 'Booked', 'booked', 'pending', 'new order']);
            } else {
                $query->where('status', 'LIKE', "%{$request->status}%");
            }
        }

        $couriers = \App\Models\Courier::where('is_active', true)->pluck('name');

        $shipments = $query->paginate(15)->withQueryString();

        return view('admin.shipments.index', compact('shipments', 'couriers'));
    }

    public function create()
    {
        $sellers = \App\Models\User::whereIn('role', ['seller', 'aggregator', 'corporate', 'b2b_customer'])->get();
        return view('admin.shipments.create', compact('sellers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_id' => 'nullable|string',
            'pickup_name' => 'nullable|string|max:255',
            'pickup_phone' => 'nullable|string|max:20',
            'pickup_pincode' => 'required|string',
            'pickup_address' => 'required|string',
            'pickup_city' => 'required|string',
            'pickup_state' => 'nullable|string|max:255',
            'receiver_name' => 'required|string',
            'receiver_phone' => 'required|string',
            'delivery_address' => 'required|string',
            'delivery_city' => 'required|string',
            'delivery_pincode' => 'required|string',
            'weight_kg' => 'required|numeric',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'is_cod' => 'required|boolean',
            'invoice_value' => 'required|numeric',
            'product_name' => 'nullable|string'
        ]);

        $routing = app(\App\Services\ServiceabilityService::class)->determineRouting($validated['delivery_pincode']);
        if (!$routing['serviceable']) {
            return back()->with('error', 'Delivery pincode unserviceable');
        }

        try {
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
                $routing['provider_id']
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $totalAmount = $rateData['total'];

        try {
            app(\App\Services\WalletService::class)->deduct(
                $validated['user_id'],
                $totalAmount,
                'Admin Manual Booking Deduction'
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $validated['shipping_charge'] = $totalAmount;
        $validated['total_amount'] = $totalAmount;
        $validated['shipment_type'] = 'B2C';

        try {
            $shipment = app(\App\Services\ShipmentService::class)->createShipment($validated, $validated['user_id']);
            return redirect()->route('admin.shipments.index')->with('success', 'Shipment created successfully. AWB: ' . $shipment->awb_number);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Find shipment by AWB number or primary ID.
     */
    protected function findShipment(string|int $identifier): Shipment
    {
        return Shipment::with('user')
            ->where('awb_number', $identifier)
            ->orWhere('id', is_numeric($identifier) ? (int)$identifier : 0)
            ->firstOrFail();
    }

    public function printLabel(Request $request, $awb)
    {
        $shipment = $this->findShipment($awb);
        
        $settings = $shipment->user?->label_settings ?? [
            'label_type' => 'thermal',
            'enable_product_name' => true,
            'enable_consignee_contact' => true,
            'enable_consignee_address' => true,
            'enable_support_contact' => true,
            'enable_support_email' => true,
            'enable_rto_address' => true,
        ];

        // Generate Real Barcode safely
        $barcode = null;
        $targetAwb = !empty($shipment->courier_awb) ? $shipment->courier_awb : $shipment->awb_number;
        try {
            if (class_exists(\Picqer\Barcode\BarcodeGeneratorSVG::class)) {
                $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                $barcode = $generator->getBarcode($targetAwb, $generator::TYPE_CODE_128, 2, 60);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Barcode generation failed for {$targetAwb}: " . $e->getMessage());
            $barcode = null;
        }

        // Gracefully handle optional PDF download if requested and dependency present
        if ($request->query('download') === 'pdf' || $request->query('format') === 'pdf') {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                try {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.label', compact('shipment', 'settings', 'barcode'));
                    return $pdf->download("shipment-slip-{$shipment->awb_number}.pdf");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("PDF generation failed for {$shipment->awb_number}: " . $e->getMessage());
                }
            }
        }

        return view('seller.label', compact('shipment', 'settings', 'barcode'));
    }

    public function printLR(Request $request, $awb)
    {
        $shipment = $this->findShipment($awb);
        return view('seller.lr', compact('shipment'));
    }

    public function printInvoice(Request $request, $awb)
    {
        $shipment = $this->findShipment($awb);
        return view('seller.invoice', compact('shipment'));
    }

    public function bulkPrintLabels(Request $request)
    {
        $rawIds = $request->input('ids') ?? $request->query('ids');
        $rawAwbs = $request->input('awbs') ?? $request->query('awbs');

        $ids = [];
        if (is_array($rawIds)) {
            $ids = array_filter($rawIds);
        } elseif (is_string($rawIds) && trim($rawIds) !== '') {
            $ids = array_filter(array_map('trim', explode(',', $rawIds)));
        }

        $awbs = [];
        if (is_array($rawAwbs)) {
            $awbs = array_filter($rawAwbs);
        } elseif (is_string($rawAwbs) && trim($rawAwbs) !== '') {
            $awbs = array_filter(array_map('trim', explode(',', $rawAwbs)));
        }

        $query = Shipment::with(['user', 'pickupFranchise']);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } elseif (!empty($awbs)) {
            $query->whereIn('awb_number', $awbs);
        } else {
            return back()->with('error', 'Please select at least one shipment to print slips.');
        }

        $shipments = $query->orderBy('id', 'desc')->get();

        if ($shipments->isEmpty()) {
            return back()->with('error', 'No eligible shipments found for the selected orders.');
        }

        $settings = [
            'label_type' => 'thermal',
            'enable_product_name' => true,
            'enable_consignee_contact' => true,
            'enable_consignee_address' => true,
            'enable_support_contact' => true,
            'enable_support_email' => true,
            'enable_rto_address' => true,
        ];

        $generator = null;
        if (class_exists(\Picqer\Barcode\BarcodeGeneratorSVG::class)) {
            try {
                $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
            } catch (\Throwable $e) {
                $generator = null;
            }
        }

        $items = [];
        foreach ($shipments as $shipment) {
            $barcode = null;
            $targetAwb = !empty($shipment->courier_awb) ? $shipment->courier_awb : $shipment->awb_number;
            if ($generator && !empty($targetAwb)) {
                try {
                    $barcode = $generator->getBarcode($targetAwb, $generator::TYPE_CODE_128, 2, 60);
                } catch (\Throwable $e) {
                    $barcode = null;
                }
            }
            $items[] = [
                'shipment' => $shipment,
                'barcode' => $barcode,
            ];
        }

        $idsString = implode(',', $shipments->pluck('id')->toArray());

        if ($request->query('download') === 'pdf' || $request->input('download') === 'pdf' || $request->query('format') === 'pdf') {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                try {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.bulk-labels', compact('items', 'settings', 'idsString'));
                    return $pdf->download("bulk-shipment-slips-" . count($items) . "-orders.pdf");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Bulk PDF generation failed: " . $e->getMessage());
                }
            }
        }

        return view('seller.bulk-labels', compact('items', 'settings', 'idsString'));
    }

    public function cancel($id)
    {
        $shipment = Shipment::findOrFail($id);
        
        if (strtolower(trim($shipment->status)) === 'cancelled') {
            return back()->with('error', 'This shipment is already cancelled.');
        }

        $cancellableStatuses = ['new', 'manifested', 'booked', 'pickup_scheduled', 'pickup scheduled', 'pickup pending', 'pickup_pending', 'pending', 'new order'];
        $currentSt = strtolower(trim($shipment->status));
        
        if (!in_array($currentSt, $cancellableStatuses) && !str_starts_with($currentSt, 'pickup')) {
            return back()->with('error', "Shipment with status '{$shipment->status}' cannot be cancelled.");
        }

        $shipment->status = 'cancelled';
        $shipment->save();

        $alreadyDebited = (float) \App\Models\WalletTransaction::where('reference_id', $shipment->awb_number)
            ->where('type', 'debit')
            ->where('status', 'success')
            ->sum('amount');

        $alreadyCredited = (float) \App\Models\WalletTransaction::where('reference_id', $shipment->awb_number)
            ->where('type', 'credit')
            ->where('status', 'success')
            ->sum('amount');

        $refundAmount = $alreadyCredited > 0 ? 0 : ($alreadyDebited > 0 ? $alreadyDebited : (float)($shipment->total_amount ?? $shipment->shipping_charge ?? 0));

        if ($refundAmount > 0 && $shipment->user_id) {
            $user = \App\Models\User::find($shipment->user_id);
            if ($user) {
                $user->wallet_balance += $refundAmount;
                $user->save();

                \App\Models\WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $refundAmount,
                    'type' => 'credit',
                    'balance_after' => $user->wallet_balance,
                    'reference_id' => $shipment->awb_number,
                    'description' => 'Admin refund for cancelled shipment ' . $shipment->awb_number,
                    'status' => 'success',
                ]);
            }
        }

        // If order was manifested to Xpressbees, notify Xpressbees via forward cancellation API
        if (!empty($shipment->courier_awb) && stripos($shipment->courier_partner, 'xpressbees') !== false) {
            try {
                $xbService = new \App\Services\Couriers\XpressbeesService();
                $xbCancel = $xbService->cancelShipment($shipment->courier_awb, 'Cancelled by Admin');
                \Illuminate\Support\Facades\Log::info("Xpressbees cancellation for {$shipment->courier_awb}: ", $xbCancel);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Xpressbees cancellation failed for {$shipment->courier_awb}: " . $e->getMessage());
            }
        }

        \App\Models\ShipmentEvent::create([
            'shipment_id' => $shipment->id,
            'status' => 'Cancelled',
            'location' => 'Admin Operations',
            'remarks' => 'Shipment cancelled by Admin.' . (!empty($shipment->courier_awb) ? " (XB AWB {$shipment->courier_awb} cancelled on Xpressbees)" : '') . ' Refund processed to user wallet.'
        ]);

        return back()->with('success', 'Shipment cancelled successfully and amount refunded.');
    }
}
