<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lorry Receipt (LR) - {{ $shipment->awb_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 py-10">
    <div class="max-w-4xl mx-auto bg-white border border-gray-300 p-10 shadow-lg relative">
        <!-- Print Button -->
        <button onclick="window.print()" class="no-print absolute top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded font-bold shadow hover:bg-blue-700">
            <i class="fa-solid fa-print"></i> Print LR
        </button>

        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-gray-800 pb-6 mb-6">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tighter uppercase">OneStall Cargo</h1>
                <p class="text-xs text-gray-600 font-bold tracking-widest mt-1">B2B Freight & Cargo Division</p>
                <p class="text-xs text-gray-500 mt-2">123 Logistics Park, Mumbai, India<br>GSTIN: 27AAAAA1234A1Z5</p>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold text-gray-800 uppercase tracking-widest">Consignment Note (LR)</h2>
                <div class="mt-2 text-sm">
                    <span class="font-bold text-gray-500">LR No:</span> <span class="font-black text-lg text-gray-900">{{ $shipment->awb_number }}</span>
                </div>
                <div class="text-sm mt-1">
                    <span class="font-bold text-gray-500">Date:</span> <span class="font-bold text-gray-900">{{ $shipment->created_at->format('d M Y, H:i') }}</span>
                </div>
                @if(!empty($shipment->eway_bill_number))
                <div class="text-sm mt-1">
                    <span class="font-bold text-gray-500">E-Way Bill:</span> <span class="font-black text-gray-900 font-mono">{{ $shipment->eway_bill_number }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Addresses -->
        <div class="grid grid-cols-2 gap-8 mb-8">
            <!-- Consignor -->
            <div class="border border-gray-300 p-4 rounded-lg">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 pb-2 mb-2">Consignor (Sender)</h3>
                <p class="font-black text-lg text-gray-900">{{ !empty(trim($shipment->pickup_name ?? '')) ? $shipment->pickup_name : (optional($shipment->user)->company_name ?: optional($shipment->user)->name ?: (Auth::user()->name ?? 'OneStall Shipper')) }}</p>
                <p class="text-sm text-gray-700 mt-1">{{ !empty(trim($shipment->pickup_address ?? '')) ? $shipment->pickup_address : 'Origin Hub' }}<br>{{ $shipment->pickup_city ?? '' }}{{ !empty($shipment->pickup_state) ? ', ' . $shipment->pickup_state : '' }} - {{ $shipment->pickup_pincode ?? '' }}</p>
                <p class="text-sm text-gray-700 mt-1 font-semibold">Ph: {{ !empty(trim($shipment->pickup_phone ?? '')) ? $shipment->pickup_phone : (optional($shipment->user)->phone ?: (Auth::user()->phone ?? 'N/A')) }}</p>
            </div>
            
            <!-- Consignee -->
            <div class="border border-gray-300 p-4 rounded-lg bg-gray-50">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 pb-2 mb-2">Consignee (Receiver)</h3>
                <p class="font-black text-lg text-gray-900">{{ $shipment->receiver_name }}</p>
                <p class="text-sm text-gray-700 mt-1">{{ $shipment->delivery_address }}<br>{{ $shipment->delivery_city }}{{ !empty($shipment->delivery_state) ? ', ' . $shipment->delivery_state : '' }} - {{ $shipment->delivery_pincode }}</p>
                <p class="text-sm text-gray-700 mt-1 font-bold">Ph: {{ $shipment->receiver_phone ?? $shipment->delivery_phone ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Cargo Details -->
        <div class="border-2 border-gray-800 rounded-lg overflow-x-auto mb-8">
                        <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-800 text-white uppercase tracking-wider text-xs">
                        <th class="px-4 py-3">Packages</th>
                        <th class="px-4 py-3">Description of Goods</th>
                        <th class="px-4 py-3">Vehicle Type</th>
                        <th class="px-4 py-3">Actual Wt.</th>
                        <th class="px-4 py-3">Charged Wt.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-300">
                    <tr>
                        <td class="px-4 py-4 font-black text-center">{{ $shipment->packages_count ?? 1 }}</td>
                        <td class="px-4 py-4 font-bold text-gray-700">General Cargo (B2B)<br><span class="text-xs text-gray-500 font-normal">Invoice Val: ₹{{ number_format($shipment->invoice_value, 2) }}</span></td>
                        <td class="px-4 py-4 font-bold text-gray-900 uppercase">{{ $shipment->vehicle_type ?? 'PTL' }}</td>
                        <td class="px-4 py-4 font-bold">{{ $shipment->weight_kg }} KG</td>
                        <td class="px-4 py-4 font-bold">{{ $shipment->weight_kg }} KG</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / Signatures -->
        <div class="grid grid-cols-3 gap-8 mt-12 text-sm text-center pt-8 border-t border-gray-300">
            <div>
                <div class="border-b border-gray-400 w-3/4 mx-auto mb-2 h-10"></div>
                <p class="font-bold text-gray-600 uppercase text-xs tracking-wider">Consignor Signature</p>
            </div>
            <div>
                <div class="border-b border-gray-400 w-3/4 mx-auto mb-2 h-10 flex items-end justify-center">
                    <span class="text-xs text-gray-400 italic">Auto-generated</span>
                </div>
                <p class="font-bold text-gray-600 uppercase text-xs tracking-wider">OneStall Cargo (Carrier)</p>
            </div>
            <div>
                <div class="border-b border-gray-400 w-3/4 mx-auto mb-2 h-10"></div>
                <p class="font-bold text-gray-600 uppercase text-xs tracking-wider">Consignee Signature</p>
            </div>
        </div>

        <div class="mt-8 text-[10px] text-gray-400 text-center uppercase tracking-widest border-t border-dashed border-gray-200 pt-4">
            Subject to Mumbai Jurisdiction. Goods transport agency under section 65(50b) of Finance Act.
        </div>
    </div>
</body>
</html>
