<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - {{ $shipment->awb_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #000;
        }
        .no-print-bar {
            width: 100%;
            max-width: 400px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 8px 12px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            box-sizing: border-box;
        }
        .no-print-bar .info {
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
        }
        .no-print-bar .btns {
            display: flex;
            gap: 6px;
        }
        .btn-print {
            cursor: pointer;
            background: #1e1b4b;
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-print:hover {
            background: #312e81;
        }
        .btn-close {
            cursor: pointer;
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        .btn-close:hover {
            background: #e5e7eb;
        }

        /* Label Structure matching sample */
        .label-container {
            width: 100%;
            max-width: 400px;
            min-height: 580px;
            background: #fff;
            border: 2px solid #000;
            box-sizing: border-box;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
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
            body { padding: 0; background: #fff; display: block; }
            .label-container { border: 2px solid #000; width: 4in; height: auto; min-height: 6in; margin: 0 auto; padding: 0; box-shadow: none; }
            .barcode-bars { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media (max-width: 420px) {
            body { padding: 8px; }
            .header-logo { font-size: 16px; }
            .barcode-text { font-size: 16px; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print-bar">
        <div class="info">
            AWB: {{ $shipment->awb_number }}
        </div>
        <div class="btns">
            <button type="button" onclick="window.print()" class="btn-print">
                Print / Save as PDF
            </button>
            <button type="button" onclick="window.close()" class="btn-close">
                Close
            </button>
        </div>
    </div>

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

        <!-- Consignee Details (Customer name, complete address & mobile number clearly visible) -->
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

        <!-- Return Address (Seller Address visible, Seller personal mobile number hidden) -->
        <div class="section-box">
            <div class="section-title">Return Address:</div>
            <div class="section-content" style="margin-bottom: 4px;">
                <span style="font-weight: 700;">{{ !empty(trim($shipment->pickup_name ?? '')) ? $shipment->pickup_name : (optional($shipment->user)->company_name ?: optional($shipment->user)->brand_name ?: optional($shipment->pickupFranchise)->company_name ?: optional($shipment->user)->name ?: 'OneStall Shipper') }}</span><br>
                {{ !empty(trim($shipment->pickup_address ?? '')) ? $shipment->pickup_address : (optional($shipment->user)->company_address ?: optional($shipment->pickupFranchise)->address ?: 'Origin Hub') }}, {{ !empty(trim($shipment->pickup_city ?? '')) ? $shipment->pickup_city : (optional($shipment->user)->company_city ?: 'City') }}{{ !empty($shipment->pickup_state) ? ', ' . $shipment->pickup_state : (!empty(optional($shipment->user)->company_state) ? ', ' . optional($shipment->user)->company_state : (!empty(optional($shipment->pickupFranchise)->state) ? ', ' . optional($shipment->pickupFranchise)->state : '')) }}, {{ !empty(trim($shipment->pickup_pincode ?? '')) ? $shipment->pickup_pincode : (optional($shipment->user)->company_pincode ?: '000000') }}, India
            </div>
            <div class="section-content">
                <strong>Mobile:</strong> **********
            </div>
        </div>

        <!-- Support Details (Seller Email & Support Contact Number provided at the bottom) -->
        <div class="support-box">
            <div class="support-title">Support Details</div>
            <div>Contact No: {{ !empty(trim($shipment->pickup_phone ?? '')) ? $shipment->pickup_phone : (optional($shipment->user)->support_phone ?: optional($shipment->user)->phone ?: '8384868758') }}</div>
            <div>Email ID: {{ optional($shipment->user)->email ?? 'rishisolidgroup@gmail.com' }}</div>
        </div>
    </div>
</body>
</html>
