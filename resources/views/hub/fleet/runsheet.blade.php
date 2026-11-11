<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Run Sheet (DRS) - {{ $runSheetNumber }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: #ffffff; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 antialiased font-sans py-6">

    <!-- Top Action Toolbar (Hidden in Print) -->
    <div class="no-print max-w-4xl mx-auto mb-4 flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <div class="flex items-center gap-3">
            <a href="{{ route('hub.fleet.report', ['id' => $rider->id, 'date' => $selectedDate]) }}" class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg text-xs transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Back to Report
            </a>
            <span class="text-xs text-gray-500 font-medium">Date: <strong class="text-gray-900">{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</strong></span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('hub.fleet.runsheet.csv', ['id' => $rider->id, 'date' => $selectedDate]) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow transition">
                <i class="fa-solid fa-file-excel mr-1"></i> Download CSV
            </a>
            <button onclick="window.print()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-lg text-xs shadow-md transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print Run Sheet (DRS)
            </button>
        </div>
    </div>

    <!-- Official DRS Document Container -->
    <div class="max-w-4xl mx-auto bg-white border border-gray-400 p-8 rounded-lg shadow-xl print:shadow-none print:border-black text-[11px] leading-tight">
        
        <!-- Header Banner -->
        <div class="border-b-2 border-gray-900 pb-4 mb-4 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tight">OneStatall Cargo</h1>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">{{ $hubName }}{{ $hubCity ? ' &bull; ' . $hubCity : '' }}{{ $hubState ? ', ' . $hubState : '' }}</p>
                <p class="text-xs font-black text-indigo-900 uppercase tracking-wider mt-1">Daily Run Sheet & Handover Manifest (DRS)</p>
            </div>

            <div class="text-right space-y-1">
                <div>
                    <span class="text-gray-500 font-bold uppercase text-[10px]">DRS Number:</span>
                    <span class="font-mono font-black text-sm text-gray-900 ml-1">{{ $runSheetNumber }}</span>
                </div>
                <div>
                    <span class="text-gray-500 font-bold text-[10px]">Run Date:</span>
                    <span class="font-bold text-gray-900 ml-1">{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y (l)') }}</span>
                </div>
                <div>
                    <span class="text-gray-500 font-bold text-[10px]">Printed:</span>
                    <span class="text-gray-600 ml-1">{{ now()->format('d-m-Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Rider / Driver & Vehicle Info Strip -->
        <div class="bg-gray-50 border border-gray-300 rounded p-3 mb-4 grid grid-cols-4 gap-3">
            <div>
                <span class="text-[9px] uppercase font-bold text-gray-400 block">Rider / Driver Name</span>
                <span class="font-black text-gray-900 text-xs">{{ $rider->user->name }}</span>
            </div>
            <div>
                <span class="text-[9px] uppercase font-bold text-gray-400 block">Phone Number</span>
                <span class="font-bold text-gray-800">{{ $rider->user->phone }}</span>
            </div>
            <div>
                <span class="text-[9px] uppercase font-bold text-gray-400 block">Vehicle Type</span>
                <span class="font-bold text-gray-800 uppercase">{{ $rider->vehicle_type ?? 'Bike' }}</span>
            </div>
            <div>
                <span class="text-[9px] uppercase font-bold text-gray-400 block">Vehicle Registration</span>
                <span class="font-mono font-black text-gray-900">{{ $rider->vehicle_number ?? 'DL-HUB-OFFICIAL' }}</span>
            </div>
        </div>

        <!-- Operational Summary Counters -->
        <div class="grid grid-cols-5 gap-2 text-center mb-4">
            <div class="border border-indigo-200 bg-indigo-50/50 p-2 rounded">
                <div class="text-[9px] font-extrabold uppercase text-indigo-700">Given for Delivery</div>
                <div class="text-base font-black text-indigo-950">{{ $totalGiven }}</div>
                <div class="text-[8px] text-gray-500">Dispatch Parcels</div>
            </div>
            <div class="border border-green-200 bg-green-50/50 p-2 rounded">
                <div class="text-[9px] font-extrabold uppercase text-green-700">Delivered</div>
                <div class="text-base font-black text-green-950">{{ $totalDeliveries }}</div>
                <div class="text-[8px] text-gray-500">Successful</div>
            </div>
            <div class="border border-red-200 bg-red-50/50 p-2 rounded">
                <div class="text-[9px] font-extrabold uppercase text-red-700">Returns (NDR)</div>
                <div class="text-base font-black text-red-950">{{ $totalReturnedToHub }}</div>
                <div class="text-[8px] text-gray-500">Back to Hub</div>
            </div>
            <div class="border border-purple-200 bg-purple-50/50 p-2 rounded">
                <div class="text-[9px] font-extrabold uppercase text-purple-700">Pickups Recd</div>
                <div class="text-base font-black text-purple-950">{{ $totalPickupsReceived }}</div>
                <div class="text-[8px] text-gray-500">From Sellers</div>
            </div>
            <div class="border border-emerald-300 bg-emerald-50 p-2 rounded">
                <div class="text-[9px] font-extrabold uppercase text-emerald-800">Hard Cash</div>
                <div class="text-base font-black text-emerald-950">&#8377;{{ number_format($totalCash, 0) }}</div>
                <div class="text-[8px] text-emerald-700 font-bold">To Handover</div>
            </div>
        </div>

        <!-- SECTION 1: Parcels Given For Delivery -->
        <div class="mb-5">
            <div class="bg-gray-900 text-white px-3 py-1.5 font-black uppercase text-[10px] tracking-wider rounded-t flex justify-between items-center">
                <span>1. Outward Handover: Parcels Given For Delivery (दिए गए पार्सल)</span>
                <span>Count: {{ $givenForDelivery->count() }}</span>
            </div>
            <table class="w-full border-collapse border border-gray-300 text-[10px]">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 font-extrabold uppercase">
                        <th class="border border-gray-300 p-1.5 text-center w-8">#</th>
                        <th class="border border-gray-300 p-1.5">AWB Number</th>
                        <th class="border border-gray-300 p-1.5">Customer & Address</th>
                        <th class="border border-gray-300 p-1.5 text-center w-16">Phone</th>
                        <th class="border border-gray-300 p-1.5 text-center w-14">Type</th>
                        <th class="border border-gray-300 p-1.5 text-right w-20">COD Amount</th>
                        <th class="border border-gray-300 p-1.5 text-center w-20">Status</th>
                        <th class="border border-gray-300 p-1.5 text-center w-28">Receiver Signature / OTP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($givenForDelivery as $idx => $s)
                    <tr class="{{ $idx % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="border border-gray-300 p-1.5 text-center font-bold">{{ $idx + 1 }}</td>
                        <td class="border border-gray-300 p-1.5 font-mono font-black text-indigo-900">{{ $s->awb_number }}</td>
                        <td class="border border-gray-300 p-1.5">
                            <div class="font-bold text-gray-900">{{ $s->receiver_name }}</div>
                            <div class="text-[9px] text-gray-600 truncate max-w-xs">{{ $s->delivery_address }}, {{ $s->delivery_city }} - {{ $s->delivery_pincode }}</div>
                        </td>
                        <td class="border border-gray-300 p-1.5 text-center font-semibold">{{ $s->receiver_phone }}</td>
                        <td class="border border-gray-300 p-1.5 text-center font-bold {{ $s->is_cod ? 'text-amber-800' : 'text-blue-800' }}">
                            {{ $s->is_cod ? 'COD' : 'Prepaid' }}
                        </td>
                        <td class="border border-gray-300 p-1.5 text-right font-black">
                            @if($s->is_cod)
                                &#8377;{{ number_format($s->cod_amount > 0 ? $s->cod_amount : $s->invoice_value, 2) }}
                            @else
                                &#8377;0.00
                            @endif
                        </td>
                        <td class="border border-gray-300 p-1.5 text-center font-bold">
                            {{ $s->status }}
                        </td>
                        <td class="border border-gray-300 p-1.5 text-center text-gray-400 font-mono text-[9px]">
                            @if($s->status === 'Delivered')
                                <span class="text-green-700 font-bold">&#10003; Delivered ({{ $s->payment_type ?? 'Cash' }})</span>
                            @elseif($s->status === 'NDR')
                                <span class="text-red-700 font-bold">&#10007; NDR ({{ $s->ndr_reason ?? 'Failed' }})</span>
                            @else
                                ____________________
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="border border-gray-300 p-3 text-center text-gray-400">No delivery parcels assigned for this date.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- SECTION 2: Pickups Received at Hub -->
        <div class="mb-5">
            <div class="bg-gray-800 text-white px-3 py-1.5 font-black uppercase text-[10px] tracking-wider rounded-t flex justify-between items-center">
                <span>2. Inward Handover: Pickups Received at Hub (हब में जमा किए गए पिकअप)</span>
                <span>Count: {{ $receivedPickups->count() }}</span>
            </div>
            <table class="w-full border-collapse border border-gray-300 text-[10px]">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 font-extrabold uppercase">
                        <th class="border border-gray-300 p-1.5 text-center w-8">#</th>
                        <th class="border border-gray-300 p-1.5">AWB Number</th>
                        <th class="border border-gray-300 p-1.5">Seller / Store</th>
                        <th class="border border-gray-300 p-1.5">Pickup Address</th>
                        <th class="border border-gray-300 p-1.5 text-center w-16">Weight</th>
                        <th class="border border-gray-300 p-1.5 text-center w-24">Status</th>
                        <th class="border border-gray-300 p-1.5 text-center w-28">Hub Inward Verified</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receivedPickups as $idx => $p)
                    <tr class="{{ $idx % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="border border-gray-300 p-1.5 text-center font-bold">{{ $idx + 1 }}</td>
                        <td class="border border-gray-300 p-1.5 font-mono font-black text-purple-900">{{ $p->awb_number }}</td>
                        <td class="border border-gray-300 p-1.5 font-bold">{{ !empty(trim($p->pickup_name ?? '')) ? $p->pickup_name : (optional($p->user)->company_name ?? optional($p->user)->name ?? 'Seller') }}</td>
                        <td class="border border-gray-300 p-1.5 text-[9px] text-gray-600">{{ !empty(trim($p->pickup_address ?? '')) ? $p->pickup_address . (!empty($p->pickup_city) ? ', ' . $p->pickup_city : '') . (!empty($p->pickup_pincode) ? ' - ' . $p->pickup_pincode : '') : 'Registered Seller Warehouse' }}</td>
                        <td class="border border-gray-300 p-1.5 text-center font-bold">{{ $p->weight_kg }} KG</td>
                        <td class="border border-gray-300 p-1.5 text-center font-bold text-purple-800">{{ $p->status }}</td>
                        <td class="border border-gray-300 p-1.5 text-center font-bold text-green-700">&#10003; Received into Hub</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="border border-gray-300 p-3 text-center text-gray-400">No pickups deposited at hub on this date.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- SECTION 3: Undelivered / NDR Returns Received at Hub -->
        <div class="mb-5">
            <div class="bg-gray-800 text-white px-3 py-1.5 font-black uppercase text-[10px] tracking-wider rounded-t flex justify-between items-center">
                <span>3. Inward Handover: Undelivered / Returns Back in Hub (वापस प्राप्त पार्सल)</span>
                <span>Count: {{ $returnedToHub->count() }}</span>
            </div>
            <table class="w-full border-collapse border border-gray-300 text-[10px]">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 font-extrabold uppercase">
                        <th class="border border-gray-300 p-1.5 text-center w-8">#</th>
                        <th class="border border-gray-300 p-1.5">AWB Number</th>
                        <th class="border border-gray-300 p-1.5">Consignee Name</th>
                        <th class="border border-gray-300 p-1.5">Destination City</th>
                        <th class="border border-gray-300 p-1.5">NDR Reason</th>
                        <th class="border border-gray-300 p-1.5 text-center w-28">Hub Custody Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returnedToHub as $idx => $r)
                    <tr class="{{ $idx % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                        <td class="border border-gray-300 p-1.5 text-center font-bold">{{ $idx + 1 }}</td>
                        <td class="border border-gray-300 p-1.5 font-mono font-black text-red-900">{{ $r->awb_number }}</td>
                        <td class="border border-gray-300 p-1.5 font-bold">{{ $r->receiver_name }}</td>
                        <td class="border border-gray-300 p-1.5">{{ $r->delivery_city }}</td>
                        <td class="border border-gray-300 p-1.5 text-red-700 font-medium">{{ $r->ndr_reason ?: 'Customer Not Available / Gate Closed' }}</td>
                        <td class="border border-gray-300 p-1.5 text-center font-bold text-gray-800">&#10003; In Hub Safe Stock</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="border border-gray-300 p-3 text-center text-gray-400">No undelivered returns received on this date.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Cash Handover Statement -->
        <div class="border-2 border-gray-800 bg-gray-50 p-3 rounded mb-6 flex justify-between items-center text-[11px]">
            <div>
                <span class="font-extrabold uppercase text-gray-600 block text-[9px]">Cash Handover to Hub Cashier</span>
                <span class="text-sm font-black text-gray-900">Total Cash Deposited: &#8377;{{ number_format($totalCash, 2) }}</span>
                <span class="text-xs text-blue-700 font-bold ml-3">(UPI Online: &#8377;{{ number_format($totalUpi, 2) }})</span>
            </div>
            <div class="text-right">
                <span class="text-[9px] uppercase font-bold text-gray-500 block">Total COD Remitted</span>
                <span class="text-base font-black text-emerald-800">&#8377;{{ number_format($totalCodCollected, 2) }}</span>
            </div>
        </div>

        <!-- Dual Signature Declaration -->
        <div class="grid grid-cols-2 gap-8 pt-4 border-t-2 border-gray-900 text-[10px]">
            <div class="border border-gray-300 p-3 rounded text-center">
                <p class="font-bold text-gray-800 uppercase">Rider / Driver Declaration & Signature</p>
                <p class="text-[9px] text-gray-500 italic mt-1 leading-tight">
                    "मैंने डिलीवरी हेतु उपरोक्त पार्सल प्राप्त किए थे। कार्य समाप्ति पर सभी अनिस्तारित पार्सल, सेलर से प्राप्त पिकअप और कुल COD कैश हब में सही सलामत जमा करवा दिया है।"
                </p>
                <div class="border-b border-gray-400 w-44 mx-auto mt-8 mb-1"></div>
                <p class="font-black text-gray-900">{{ $rider->user->name }}</p>
                <p class="text-[9px] text-gray-400">Date & Time: _________________</p>
            </div>

            <div class="border border-gray-300 p-3 rounded text-center">
                <p class="font-bold text-gray-800 uppercase">Hub Supervisor / Manager Verification</p>
                <p class="text-[9px] text-gray-500 italic mt-1 leading-tight">
                    "हब में राइडर द्वारा वापस लाए गए सभी पार्सल (NDR), नए कलेक्ट किए गए पिकअप और हार्ड कैश राशि (₹{{ number_format($totalCash, 2) }}) का भौतिक मिलान कर सत्यापित कर लिया गया है।"
                </p>
                <div class="border-b border-gray-400 w-44 mx-auto mt-8 mb-1"></div>
                <p class="font-black text-gray-900">Hub In-Charge / Cashier</p>
                <p class="text-[9px] text-gray-400">Date & Seal: _________________</p>
            </div>
        </div>

    </div>

</body>
</html>
