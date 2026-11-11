<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Shipping Slips ({{ count($items) }} Orders) - OneStall Cargo</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
            color: #000;
        }
        .no-print-bar {
            position: sticky;
            top: 10px;
            z-index: 999;
            width: 100%;
            max-width: 600px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e1b4b;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-size: 13px;
            font-weight: 700;
        }
        .no-print-bar .btns {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-action {
            cursor: pointer;
            border: none;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-print {
            background: #4f46e5;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #4338ca;
        }
        .btn-download {
            background: #059669;
            color: #ffffff;
        }
        .btn-download:hover {
            background: #047857;
        }
        .btn-close {
            background: #374151;
            color: #e5e7eb;
        }
        .btn-close:hover {
            background: #4b5563;
        }

        .labels-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .label-page {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            page-break-after: always;
            break-after: page;
        }
        .label-page:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 0;
        }

        /* Label Structure matching standard 4x6" thermal slip */
        .label-container {
            width: 100%;
            max-width: 400px;
            min-height: 580px;
            background: #ffffff;
            border: 2px solid #000;
            box-sizing: border-box;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            color: #000;
        }

        .label-row {
            display: flex;
            width: 100%;
            border-bottom: 1.5px solid #000;
            box-sizing: border-box;
        }
        .cell-left {
            width: 60%;
            padding: 8px 10px;
            box-sizing: border-box;
            font-size: 12px;
            font-weight: 700;
            border-right: 1.5px solid #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .cell-right {
            width: 40%;
            padding: 8px 10px;
            box-sizing: border-box;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .header-logo {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #000;
            line-height: 1.1;
        }
        .header-tagline {
            font-size: 8.5px;
            color: #555;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .barcode-area {
            text-align: center;
            border-bottom: 1.5px solid #000;
            padding: 10px 10px 8px 10px;
            box-sizing: border-box;
        }
        .barcode-bars {
            height: 55px;
            background: repeating-linear-gradient(90deg, #000, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 8px, #fff 8px, #fff 10px, #000 10px, #000 14px, #fff 14px, #fff 18px);
            margin-bottom: 6px;
            width: 100%;
        }
        .barcode-text {
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 2px;
            color: #000;
        }

        .section-box {
            padding: 8px 10px;
            border-bottom: 1.5px solid #000;
            font-size: 11px;
            line-height: 1.35;
            box-sizing: border-box;
            word-wrap: break-word;
            word-break: break-word;
        }
        .section-title {
            font-size: 11px;
            font-weight: 800;
            color: #000;
            margin-bottom: 2px;
        }
        .section-content {
            font-size: 11px;
            color: #111;
            font-weight: 600;
        }

        .support-box {
            padding: 8px 10px;
            text-align: center;
            font-size: 11px;
            line-height: 1.4;
            box-sizing: border-box;
            margin-top: auto;
        }
        .support-title {
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        @media print {
            .no-print, .no-print-bar { display: none !important; }
            body { padding: 0; margin: 0; background: #fff; display: block; }
            .labels-wrapper { display: block; width: 100%; }
            .label-page {
                display: block;
                page-break-after: always !important;
                break-after: page !important;
                margin: 0 !important;
                padding: 0 !important;
                height: 100%;
            }
            .label-page:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            .label-container {
                border: 2px solid #000 !important;
                width: 4in !important;
                height: auto !important;
                min-height: 6in !important;
                margin: 0 auto !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
            .barcode-bars { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="@if(!request()->has('download') && !request()->has('noprint'))window.print()@endif">

    <div class="no-print-bar">
        <div>
            Bulk Slips: <span style="color: #a5b4fc;">{{ count($items) }} Orders</span>
        </div>
        <div class="btns">
            <button type="button" onclick="window.print()" class="btn-action btn-print">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print All ({{ count($items) }})
            </button>
            <a href="{{ request()->fullUrlWithQuery(['download' => 'pdf']) }}" class="btn-action btn-download">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download PDF
            </a>
            <button type="button" onclick="window.close()" class="btn-action btn-close">Close</button>
        </div>
    </div>

    <div class="labels-wrapper">
        @foreach($items as $index => $item)
            @php
                $shipment = $item['shipment'];
                $barcode = $item['barcode'] ?? null;
            @endphp
            <div class="label-page">
                <div class="label-container">
                    <!-- Row 1: Logo & Order ID -->
                    <div class="label-row">
                        <div class="cell-left">
                            <div class="header-logo">ONESTALL CARGO</div>
                            <div class="header-tagline">delivering logistics happiness</div>
                        </div>
                        <div class="cell-right">
                            <div>Order ID: <span style="font-weight: 800;">{{ $shipment->order_id ?? $shipment->awb_number }}</span></div>
                        </div>
                    </div>

                    <!-- Row 2: AWB NO & Payment -->
                    <div class="label-row">
                        <div class="cell-left">
                            <div>AWB NO: <span style="font-weight: 800;">{{ $shipment->awb_number }}</span></div>
                        </div>
                        <div class="cell-right">
                            <div style="font-weight: 800; font-size: 13px;">
                                @if($shipment->is_cod)
                                    COD ₹ {{ number_format($shipment->cod_amount ?? $shipment->invoice_value, 2) }}
                                @else
                                    PREPAID
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Product Name & Qty -->
                    <div class="label-row">
                        <div class="cell-left">
                            <div>Product Name: <span style="font-weight: 700;">{{ $shipment->product_name ?? 'General Cargo' }}</span>@if(!empty($shipment->weight_kg)) <span style="font-size: 10px; color: #555;">({{ $shipment->weight_kg }} KG)</span>@endif</div>
                        </div>
                        <div class="cell-right">
                            <div>Qty: <span style="font-weight: 800;">{{ $shipment->product_qty ?? 1 }}</span></div>
                        </div>
                    </div>

                    <!-- Row 4: Barcode Area -->
                    <div class="barcode-area">
                        <div style="margin-bottom: 4px; display: flex; justify-content: center; width: 100%;">
                            @if(!empty($barcode))
                                {!! $barcode !!}
                            @else
                                <div class="barcode-bars"></div>
                            @endif
                        </div>
                        <div class="barcode-text">{{ $shipment->awb_number }}</div>
                    </div>

                    <!-- Consignee Details -->
                    <div class="section-box">
                        <div class="section-title">Consignee Name:</div>
                        <div class="section-content" style="font-weight: 800; font-size: 12px; margin-bottom: 4px;">{{ $shipment->receiver_name }}</div>
                        
                        <div class="section-title">Shipping Address:</div>
                        <div class="section-content" style="margin-bottom: 4px;">
                            {{ $shipment->delivery_address }}{{ !empty($shipment->delivery_city) ? ', ' . $shipment->delivery_city : '' }}{{ !empty($shipment->delivery_state) ? ', ' . $shipment->delivery_state : '' }}, {{ $shipment->delivery_pincode }}, {{ strtoupper($shipment->destination_country ?? 'India') }}
                        </div>
                        
                        <div class="section-content">
                            <strong>Mobile:</strong> {{ $shipment->receiver_phone }}
                        </div>
                    </div>

                    <!-- Return Address -->
                    @php
                        $matchedWh = null;
                        if ($shipment->user && $shipment->user->relationLoaded('warehouses')) {
                            $matchedWh = $shipment->user->warehouses->firstWhere('pincode', $shipment->pickup_pincode) 
                                ?? $shipment->user->warehouses->firstWhere('is_default', 1) 
                                ?? $shipment->user->warehouses->first();
                        } elseif ($shipment->user) {
                            $matchedWh = \App\Models\Warehouse::where('user_id', $shipment->user_id)->where('pincode', $shipment->pickup_pincode)->first()
                                ?? \App\Models\Warehouse::where('user_id', $shipment->user_id)->where('is_default', 1)->first()
                                ?? \App\Models\Warehouse::where('user_id', $shipment->user_id)->first();
                        }

                        $labelShipperName = !empty(trim($shipment->pickup_name ?? '')) 
                            ? $shipment->pickup_name 
                            : ($matchedWh ? ($matchedWh->contact_person ? $matchedWh->contact_person . ' (' . $matchedWh->name . ')' : $matchedWh->name) : (optional($shipment->user)->company_name ?: optional($shipment->user)->brand_name ?: optional($shipment->pickupFranchise)->company_name ?: optional($shipment->user)->name ?: 'OneStall Shipper'));

                        $labelShipperAddr = !empty(trim($shipment->pickup_address ?? '')) 
                            ? $shipment->pickup_address 
                            : ($matchedWh->address ?? (optional($shipment->user)->company_address ?: optional($shipment->pickupFranchise)->address ?: 'Origin Hub'));

                        $labelShipperCity = !empty(trim($shipment->pickup_city ?? '')) 
                            ? $shipment->pickup_city 
                            : ($matchedWh->city ?? (optional($shipment->user)->company_city ?: 'City'));

                        $labelShipperState = !empty(trim($shipment->pickup_state ?? '')) 
                            ? $shipment->pickup_state 
                            : ($matchedWh->state ?? (!empty(optional($shipment->user)->company_state) ? optional($shipment->user)->company_state : (!empty(optional($shipment->pickupFranchise)->state) ? optional($shipment->pickupFranchise)->state : '')));

                        $labelShipperPin = !empty(trim($shipment->pickup_pincode ?? '')) 
                            ? $shipment->pickup_pincode 
                            : ($matchedWh->pincode ?? (optional($shipment->user)->company_pincode ?: '000000'));
                    @endphp
                    <div class="section-box">
                        <div class="section-title">Return Address:</div>
                        <div class="section-content" style="margin-bottom: 4px;">
                            <span style="font-weight: 700;">{{ $labelShipperName }}</span><br>
                            {{ $labelShipperAddr }}, {{ $labelShipperCity }}{{ !empty($labelShipperState) ? ', ' . $labelShipperState : '' }}, {{ $labelShipperPin }}, India
                        </div>
                        <div class="section-content">
                            <strong>Mobile:</strong> **********
                        </div>
                    </div>

                    <!-- Support Details -->
                    <div class="support-box">
                        <div class="support-title">Support Details</div>
                        <div>Contact No: {{ !empty(trim($shipment->pickup_phone ?? '')) ? $shipment->pickup_phone : (optional($shipment->user)->support_phone ?: optional($shipment->user)->phone ?: (function_exists('setting') ? (setting('support_phone') ?: '1800-202-3000') : '1800-202-3000')) }}</div>
                        <div>Email ID: {{ optional($shipment->user)->email ?? (function_exists('setting') ? (setting('support_email') ?: 'support@onestallcargo.com') : 'support@onestallcargo.com') }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
