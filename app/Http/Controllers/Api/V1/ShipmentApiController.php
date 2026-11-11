<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shipment;
use Illuminate\Support\Str;

class ShipmentApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $shipments = Shipment::where("user_id", $user->id)
            ->orderBy("created_at", "desc")
            ->paginate(20);

        return response()->json([
            "success" => true,
            "data" => $shipments
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "pickup_pincode" => "required|string",
            "pickup_name" => "nullable|string|max:255",
            "pickup_phone" => "nullable|string|max:20",
            "pickup_address" => "nullable|string",
            "pickup_city" => "nullable|string|max:255",
            "pickup_state" => "nullable|string|max:255",
            "shipment_type" => "required|string",
            "receiver_name" => "required|string",
            "receiver_phone" => "required|string",
            "delivery_address" => "required|string",
            "delivery_city" => "required|string",
            "delivery_pincode" => "required|string",
            "weight_kg" => "required|numeric",
            "is_cod" => "required|boolean",
            "invoice_value" => "required|numeric",
            "length_cm" => "nullable|numeric",
            "width_cm" => "nullable|numeric",
            "height_cm" => "nullable|numeric",
            "product_name" => "nullable|string",
            "product_sku" => "nullable|string",
            "product_qty" => "nullable|integer",
        ]);

        $user = $request->user();

        // KYC Check
        $kyc = \App\Models\Kyc::where("user_id", $user->id)->first();
        if (!$kyc || $kyc->status !== "approved") {
            return response()->json(["success" => false, "message" => "KYC Verification Required!"], 403);
        }

        $routing = app(\App\Services\ServiceabilityService::class)->determineRouting($validated["delivery_pincode"]);
        if (!$routing["serviceable"]) {
            return response()->json(["success" => false, "message" => "Delivery pincode is unserviceable."], 400);
        }

        try {
            $rateData = app(\App\Services\PricingService::class)->calculateRate(
                $routing["fulfillment_type"],
                $validated["pickup_pincode"],
                $validated["delivery_pincode"],
                $validated["weight_kg"],
                $validated["length_cm"] ?? 10,
                $validated["width_cm"] ?? 10,
                $validated["height_cm"] ?? 10,
                $validated["is_cod"],
                $validated["invoice_value"],
                false, false, 0,
                $routing["provider_id"]
            );
        } catch (\Exception $e) {
            return response()->json(["success" => false, "message" => "Pricing Error: " . $e->getMessage()], 400);
        }

        $totalAmount = $rateData["total"];

        $shipment = new Shipment();
        $shipment->user_id = $user->id;
        $shipment->awb_number = app(\App\Services\AwbGeneratorService::class)->generateUniqueAwb();

        try {
            app(\App\Services\WalletService::class)->deduct(
                $user->id, 
                $totalAmount, 
                "Shipment Booking API Deduction", 
                $shipment->awb_number
            );
        } catch (\Exception $e) {
            return response()->json(["success" => false, "message" => $e->getMessage()], 402);
        }

        $shipment->shipping_charge = $totalAmount;
        $shipment->total_amount = $totalAmount;
        $shipment->shipment_type = $validated["shipment_type"];
        $shipment->receiver_name = $validated["receiver_name"];
        $shipment->receiver_phone = $validated["receiver_phone"];
        $warehouse = \App\Models\Warehouse::where('user_id', $user->id)->orderByDesc('is_default')->first();
        $shipment->pickup_name = !empty(trim($validated['pickup_name'] ?? '')) ? $validated['pickup_name'] : ($warehouse ? ($warehouse->contact_person ? $warehouse->contact_person . ' (' . $warehouse->name . ')' : $warehouse->name) : $user->company_name);
        $shipment->pickup_phone = !empty(trim($validated['pickup_phone'] ?? '')) ? $validated['pickup_phone'] : ($warehouse->phone ?? $user->phone);
        $shipment->pickup_address = !empty(trim($validated['pickup_address'] ?? '')) ? $validated['pickup_address'] : ($warehouse->address ?? $user->company_address ?? 'Origin Hub');
        $shipment->pickup_city = !empty(trim($validated['pickup_city'] ?? '')) ? $validated['pickup_city'] : ($warehouse->city ?? $user->company_city ?? 'Origin City');
        $shipment->pickup_state = !empty(trim($validated['pickup_state'] ?? '')) ? $validated['pickup_state'] : ($warehouse->state ?? $user->company_state ?? null);
        $shipment->pickup_pincode = $validated["pickup_pincode"];
        $shipment->delivery_address = $validated["delivery_address"];
        $shipment->delivery_city = $validated["delivery_city"];
        $shipment->delivery_pincode = $validated["delivery_pincode"];
        $shipment->weight_kg = $validated["weight_kg"];
        $shipment->is_cod = $validated["is_cod"];
        $shipment->invoice_value = $validated["invoice_value"];
        
        $shipment->product_name = $validated["product_name"] ?? "General Parcel";
        $shipment->product_sku = $validated["product_sku"] ?? "N/A";
        $shipment->product_qty = $validated["product_qty"] ?? 1;
        $shipment->status = "Pending";

        $shipment->save();
        app(\App\Services\ShipmentService::class)->assignRouting($shipment, $shipment->delivery_pincode);

        return response()->json([
            "success" => true,
            "message" => "Shipment created successfully",
            "awb_number" => $shipment->awb_number,
            "shipping_charge" => $totalAmount,
            "data" => $shipment
        ]);
    }

    public function show(Request $request, $awb)
    {
        $shipment = Shipment::where("user_id", $request->user()->id)->where("awb_number", $awb)->first();
        if (!$shipment) {
            return response()->json(["success" => false, "message" => "Shipment not found"], 404);
        }
        return response()->json(["success" => true, "data" => $shipment]);
    }

    public function cancel(Request $request, $awb)
    {
        $shipment = Shipment::where("user_id", $request->user()->id)->where("awb_number", $awb)->first();
        if (!$shipment) {
            return response()->json(["success" => false, "message" => "Shipment not found"], 404);
        }
        
        $cancellableStatuses = ['pending', 'manifested', 'booked', 'pickup scheduled', 'pickup_scheduled', 'scheduled', 'new', 'new order'];
        if (in_array(strtolower(trim($shipment->status)), $cancellableStatuses)) {
            $shipment->status = "Cancelled";
            $shipment->save();

            $alreadyDebited = (float) \App\Models\WalletTransaction::where('reference_id', $shipment->awb_number)
                ->where('type', 'debit')
                ->where('status', 'success')
                ->sum('amount');

            $refundAmount = $alreadyDebited > 0 ? $alreadyDebited : (float)($shipment->total_amount ?? $shipment->shipping_charge ?? 0);

            if ($refundAmount > 0) {
                app(\App\Services\WalletService::class)->credit(
                    $request->user()->id,
                    $refundAmount,
                    "Refund for cancelled API shipment " . $shipment->awb_number,
                    $shipment->awb_number
                );
            }
            \App\Models\ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => 'Cancelled',
                'location' => $shipment->pickup_city ?? 'Origin',
                'remarks' => 'Shipment cancelled via API.'
            ]);

            return response()->json(["success" => true, "message" => "Shipment cancelled and amount refunded."]);
        }

        return response()->json(["success" => false, "message" => "Shipment cannot be cancelled at this stage."], 400);
    }
}

