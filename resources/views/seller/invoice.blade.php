<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commercial Invoice - {{ $shipment->awb_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 py-6 sm:py-10">
    <div class="max-w-4xl mx-auto bg-white border border-gray-300 p-6 sm:p-10 shadow-lg relative rounded-lg">
        <!-- Print Button -->
        <button onclick="window.print()" class="no-print absolute top-4 right-4 text-xs sm:text-sm bg-indigo-900 text-white px-4 py-2 rounded-lg font-bold shadow hover:bg-black transition flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Print Invoice
        </button>

        <!-- Header -->
        <div class="text-center border-b-2 border-gray-800 pb-5 mb-6">
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight uppercase">Commercial Invoice</h1>
            <p class="text-xs text-gray-500 font-bold tracking-widest mt-1">OneStall Cargo Shipping Services</p>
        </div>

        <div class="flex justify-between items-start mb-6 text-sm">
            <div>
                <span class="font-bold text-gray-500">AWB No:</span> <span class="font-black text-lg text-indigo-900">{{ $shipment->awb_number }}</span><br>
                @if(!empty($shipment->order_id))
                <span class="font-bold text-gray-500">Order ID:</span> <span class="font-bold text-gray-900">{{ $shipment->order_id }}</span>
                @endif
            </div>
            <div class="text-right">
                <span class="font-bold text-gray-500">Date:</span> <span class="font-bold text-gray-900">{{ $shipment->created_at->format('d M Y') }}</span><br>
                <span class="font-bold text-gray-500">Payment Mode:</span> <span class="font-bold text-gray-900">{{ $shipment->is_cod ? 'COD' : 'Prepaid' }}</span>
                @if(!empty($shipment->eway_bill_number))
                <br><span class="font-bold text-gray-500">E-Way Bill:</span> <span class="font-black text-gray-900 font-mono">{{ $shipment->eway_bill_number }}</span>
                @endif
            </div>
        </div>

        <!-- Addresses -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-8">
            <!-- Shipper -->
            <div class="border border-gray-300 p-4 rounded-lg bg-gray-50/50">
                <h3 class="text-xs font-extrabold text-gray-600 uppercase tracking-wider border-b border-gray-200 pb-2 mb-2">Shipper (Sender)</h3>
                <p class="font-black text-base text-gray-900">{{ !empty(trim($shipment->pickup_name ?? '')) ? $shipment->pickup_name : (optional($shipment->user)->company_name ?: optional($shipment->user)->name ?: (Auth::user()->company_name ?? Auth::user()->name ?? 'OneStall Shipper')) }}</p>
                <p class="text-xs text-gray-700 mt-1">{{ !empty(trim($shipment->pickup_address ?? '')) ? $shipment->pickup_address : 'Origin Hub' }}<br>{{ $shipment->pickup_city ?? '' }}{{ !empty($shipment->pickup_state) ? ', ' . $shipment->pickup_state : (!empty(optional($shipment->user)->company_state) ? ', ' . optional($shipment->user)->company_state : '') }} - {{ $shipment->pickup_pincode ?? '' }}</p>
                <p class="text-xs text-gray-700 mt-1 font-semibold">Ph: {{ !empty(trim($shipment->pickup_phone ?? '')) ? $shipment->pickup_phone : (optional($shipment->user)->phone ?: (Auth::user()->phone ?? 'N/A')) }}</p>
            </div>
            
            <!-- Consignee -->
            <div class="border border-gray-300 p-4 rounded-lg bg-gray-50/50">
                <h3 class="text-xs font-extrabold text-gray-600 uppercase tracking-wider border-b border-gray-200 pb-2 mb-2">Consignee (Recipient)</h3>
                <p class="font-black text-base text-gray-900">{{ $shipment->receiver_name }}</p>
                <p class="text-xs text-gray-700 mt-1">{{ $shipment->delivery_address }}<br>{{ $shipment->delivery_city }}{{ !empty($shipment->delivery_state) ? ', ' . $shipment->delivery_state : '' }} - {{ $shipment->delivery_pincode }}</p>
                @if(!empty($shipment->destination_country))
                <p class="text-xs text-gray-700 mt-1 font-bold">Country: {{ $shipment->destination_country }}</p>
                @endif
                <p class="text-xs text-gray-700 mt-1 font-semibold">Ph: {{ $shipment->receiver_phone }}</p>
            </div>
        </div>

        <!-- Itemized Goods -->
        <div class="border border-gray-300 rounded-lg overflow-x-auto mb-8">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-100 text-gray-800 uppercase tracking-wider text-[11px] font-bold border-b border-gray-300">
                        <th class="px-4 py-3 text-center">Qty</th>
                        <th class="px-4 py-3">Description of Goods</th>
                        <th class="px-4 py-3">HSN / SKU</th>
                        <th class="px-4 py-3 text-center">Weight</th>
                        <th class="px-4 py-3 text-right">Unit Price</th>
                        <th class="px-4 py-3 text-right">Total Price</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-xs">
                    @php
                        $items = [];
                        if (!empty($shipment->product_details)) {
                            $decoded = json_decode($shipment->product_details, true);
                            if (is_array($decoded) && count($decoded) > 0) {
                                $items = $decoded;
                            }
                        }
                        if (empty($items)) {
                            $items[] = [
                                'name' => $shipment->product_name ?: 'Package Item',
                                'qty' => $shipment->product_qty ?: 1,
                                'price' => ($shipment->invoice_value > 0 ? $shipment->invoice_value : $shipment->total_amount) / max(1, ($shipment->product_qty ?: 1)),
                                'sku' => $shipment->product_sku ?: 'N/A'
                            ];
                        }
                        $totalInvoiceSum = 0;
                    @endphp
                    @foreach($items as $item)
                    @php
                        $itemQty = (int)($item['qty'] ?? 1);
                        $itemPrice = (float)($item['price'] ?? 0);
                        $lineTotal = $itemQty * $itemPrice;
                        $totalInvoiceSum += $lineTotal;
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-center font-bold">{{ $itemQty }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $item['name'] ?? 'Item' }}</td>
                        <td class="px-4 py-3 font-mono text-gray-500">{{ $item['sku'] ?? $shipment->hs_code ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $shipment->weight_kg }} KG</td>
                        <td class="px-4 py-3 text-right">&#8377;{{ number_format($itemPrice, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">&#8377;{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-800">
                        <td colspan="5" class="px-4 py-3 text-right font-bold uppercase tracking-widest text-xs text-gray-700">Total Invoice Value:</td>
                        <td class="px-4 py-3 text-right font-black text-base text-gray-900">&#8377;{{ number_format($totalInvoiceSum > 0 ? $totalInvoiceSum : ($shipment->invoice_value ?? $shipment->total_amount), 2) }}</td>
                    </tr>
                    @if($shipment->is_cod)
                    <tr class="bg-amber-50/60 border-t border-amber-200">
                        <td colspan="5" class="px-4 py-2.5 text-right font-bold uppercase tracking-widest text-xs text-amber-900">COD Collect Amount:</td>
                        <td class="px-4 py-2.5 text-right font-black text-base text-amber-900">&#8377;{{ number_format($shipment->cod_amount ?? $shipment->invoice_value, 2) }}</td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>

        <!-- Declarations -->
        <div class="text-xs text-gray-600 space-y-1 mb-6 border-l-2 border-indigo-900 pl-3">
            <p><strong>Declaration:</strong> We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.</p>
        </div>

        <!-- Footer / Signatures -->
        <div class="grid grid-cols-2 gap-8 text-xs pt-6 border-t border-gray-300">
            <div>
                <p class="font-bold text-gray-600 uppercase tracking-wider mb-8">Authorized Signatory</p>
                <div class="border-b border-gray-400 w-48 mb-1"></div>
                <p class="font-bold text-gray-900">{{ Auth::user()->name }}</p>
            </div>
            <div class="text-right flex flex-col justify-end">
                <p class="text-[11px] text-gray-400 italic">This is a computer generated invoice and does not require a physical signature.</p>
            </div>
        </div>
    </div>
</body>
</html>
