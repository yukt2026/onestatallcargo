@extends('layouts.hub')

@section('content')
<div class="space-y-6" x-data="{ selectedDate: '{{ $selectedDate }}', activeTab: 'given' }">
    
    <!-- Top Bar with Rider Details, Date Filter, & DRS Print Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
        <div class="flex items-center gap-4">
            <a href="{{ route('hub.fleet.index') }}" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gray-200 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-extrabold text-gray-900">{{ $rider->user->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $rider->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $rider->status }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 font-bold tracking-wide mt-0.5">
                    <i class="fa-solid fa-phone text-gray-400 mr-1"></i> {{ $rider->user->phone }} &bull;
                    <i class="fa-solid fa-motorcycle text-gray-400 mr-1"></i> {{ $rider->vehicle_type ?? 'Rider' }} ({{ $rider->vehicle_number ?? 'No Vehicle No' }})
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 text-right">Select Date</label>
                <input type="date" x-model="selectedDate" @change="window.location.href='?date=' + selectedDate" class="bg-gray-50 border border-gray-200 text-gray-800 text-xs font-bold rounded-xl px-3.5 py-2 shadow-sm outline-none focus:border-[var(--gold)]">
            </div>

            <a href="{{ route('hub.fleet.runsheet', ['id' => $rider->id, 'date' => $selectedDate]) }}" target="_blank" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center gap-1.5 self-end">
                <i class="fa-solid fa-print"></i> Print Run Sheet (DRS)
            </a>

            <a href="{{ route('hub.fleet.runsheet.csv', ['id' => $rider->id, 'date' => $selectedDate]) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center gap-1.5 self-end">
                <i class="fa-solid fa-file-excel"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- 4 Key Operational Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Given for Delivery -->
        <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-5 shadow-sm">
            <div class="text-[10px] font-extrabold text-indigo-700 uppercase tracking-widest flex justify-between items-center">
                <span>Parcels Given (दिए गए)</span>
                <i class="fa-solid fa-truck-ramp-box text-indigo-400 text-base"></i>
            </div>
            <div class="text-3xl font-black text-indigo-950 mt-2">{{ $totalGiven }}</div>
            <div class="text-[11px] text-indigo-800 mt-2 pt-2 border-t border-indigo-100/80 flex justify-between">
                <span>Delivered: <strong>{{ $totalDeliveries }}</strong></span>
                <span>NDR: <strong>{{ $totalReturnedToHub }}</strong></span>
                <span>OFD: <strong>{{ $totalPendingDeliveries }}</strong></span>
            </div>
        </div>

        <!-- Received at Hub -->
        <div class="bg-purple-50/70 border border-purple-100 rounded-2xl p-5 shadow-sm">
            <div class="text-[10px] font-extrabold text-purple-700 uppercase tracking-widest flex justify-between items-center">
                <span>Parcels Received (रिसीव किए)</span>
                <i class="fa-solid fa-boxes-packing text-purple-400 text-base"></i>
            </div>
            <div class="text-3xl font-black text-purple-950 mt-2">{{ $totalParcelsReceivedAtHub }}</div>
            <div class="text-[11px] text-purple-800 mt-2 pt-2 border-t border-purple-100/80 flex justify-between">
                <span>Pickups: <strong>{{ $totalPickupsReceived }}</strong></span>
                <span>Returns (NDR): <strong>{{ $totalReturnedToHub }}</strong></span>
            </div>
        </div>

        <!-- Hard Cash Handover -->
        <div class="bg-green-50/70 border border-green-100 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="text-[10px] font-extrabold text-green-700 uppercase tracking-widest flex justify-between items-center">
                <span>Cash to Deposit (कैश हैंडओवर)</span>
                <i class="fa-solid fa-money-bill-wave text-green-500 text-base"></i>
            </div>
            <div class="text-3xl font-black text-green-950 mt-2">&#8377;{{ number_format($totalCash, 2) }}</div>
            <div class="text-[11px] text-green-800 mt-2 pt-2 border-t border-green-100/80 flex justify-between">
                <span>Rider to Hub Cashier</span>
                <span class="font-bold uppercase text-[9px] bg-green-200 text-green-800 px-1.5 py-0.5 rounded">Physically Handover</span>
            </div>
        </div>

        <!-- UPI Auto Settled -->
        <div class="bg-blue-50/70 border border-blue-100 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="text-[10px] font-extrabold text-blue-700 uppercase tracking-widest flex justify-between items-center">
                <span>UPI Digital (ऑनलाइन संग्रह)</span>
                <i class="fa-solid fa-qrcode text-blue-500 text-base"></i>
            </div>
            <div class="text-3xl font-black text-blue-950 mt-2">&#8377;{{ number_format($totalUpi, 2) }}</div>
            <div class="text-[11px] text-blue-800 mt-2 pt-2 border-t border-blue-100/80 flex justify-between">
                <span>Total COD: &#8377;{{ number_format($totalCodCollected, 2) }}</span>
                <span class="font-bold uppercase text-[9px] bg-blue-200 text-blue-800 px-1.5 py-0.5 rounded">Auto-Settled</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-gray-200 bg-white rounded-t-2xl px-4 pt-2 shadow-sm gap-2">
        <button @click="activeTab = 'given'" :class="activeTab === 'given' ? 'border-b-2 border-indigo-600 text-indigo-700 font-extrabold' : 'text-gray-500 font-bold hover:text-gray-800'" class="py-3 px-4 text-xs flex items-center gap-2 transition">
            <i class="fa-solid fa-truck-fast"></i>
            <span>Parcels Given for Delivery (दिए गए पार्सल)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-indigo-100 text-indigo-800">{{ $givenForDelivery->count() }}</span>
        </button>

        <button @click="activeTab = 'pickups'" :class="activeTab === 'pickups' ? 'border-b-2 border-purple-600 text-purple-700 font-extrabold' : 'text-gray-500 font-bold hover:text-gray-800'" class="py-3 px-4 text-xs flex items-center gap-2 transition">
            <i class="fa-solid fa-arrow-down-to-line"></i>
            <span>Pickups Received at Hub (रिसीव किए पिकअप)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-100 text-purple-800">{{ $receivedPickups->count() }}</span>
        </button>

        <button @click="activeTab = 'returns'" :class="activeTab === 'returns' ? 'border-b-2 border-red-600 text-red-700 font-extrabold' : 'text-gray-500 font-bold hover:text-gray-800'" class="py-3 px-4 text-xs flex items-center gap-2 transition">
            <i class="fa-solid fa-rotate-left"></i>
            <span>Returns / NDR Received (वापस प्राप्त पार्सल)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-red-100 text-red-800">{{ $returnedToHub->count() }}</span>
        </button>

        <button @click="activeTab = 'cash'" :class="activeTab === 'cash' ? 'border-b-2 border-green-600 text-green-700 font-extrabold' : 'text-gray-500 font-bold hover:text-gray-800'" class="py-3 px-4 text-xs flex items-center gap-2 transition">
            <i class="fa-solid fa-cash-register"></i>
            <span>Cash Handover (कैश मिलान)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-green-100 text-green-800">&#8377;{{ number_format($totalCash, 0) }}</span>
        </button>
    </div>

    <!-- TAB 1: Parcels Given for Delivery -->
    <div x-show="activeTab === 'given'" class="bg-white rounded-b-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-extrabold text-gray-900 text-sm">
                <i class="fa-solid fa-list-check text-indigo-600 mr-2"></i> Parcels Handed Over to Rider for Delivery (Dispatch Run)
            </h3>
            <span class="text-xs text-gray-500 font-bold">{{ $givenForDelivery->count() }} Packages</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3.5">Time</th>
                        <th class="px-5 py-3.5">AWB & Order</th>
                        <th class="px-5 py-3.5">Customer & Address</th>
                        <th class="px-5 py-3.5">Payment</th>
                        <th class="px-5 py-3.5">COD Amount</th>
                        <th class="px-5 py-3.5">Delivery Status</th>
                        <th class="px-5 py-3.5">Payment Method</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($givenForDelivery as $shipment)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5 font-bold text-gray-500">
                            {{ $shipment->updated_at ? $shipment->updated_at->format('h:i A') : 'N/A' }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="font-black text-indigo-600">{{ $shipment->awb_number }}</span>
                            <div class="text-[10px] text-gray-400">Order: {{ $shipment->order_id ?? 'N/A' }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="font-bold text-gray-900">{{ $shipment->receiver_name }}</div>
                            <div class="text-[10px] text-gray-500">{{ $shipment->delivery_city }}, {{ $shipment->delivery_state ?? '' }} ({{ $shipment->delivery_pincode }})</div>
                        </td>
                        <td class="px-5 py-3.5 font-bold">
                            @if($shipment->is_cod)
                                <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded text-[10px]">COD</span>
                            @else
                                <span class="text-blue-700 bg-blue-50 px-2 py-0.5 rounded text-[10px]">Prepaid</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 font-black text-gray-900">
                            @if($shipment->is_cod)
                                &#8377;{{ number_format($shipment->cod_amount > 0 ? $shipment->cod_amount : $shipment->invoice_value, 2) }}
                            @else
                                <span class="text-gray-400 font-medium">&#8377;0.00</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @php
                                $badgeClass = match($shipment->status) {
                                    'Delivered' => 'bg-green-100 text-green-800 border-green-200',
                                    'Out for Delivery' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                    'NDR' => 'bg-amber-100 text-amber-800 border-amber-200',
                                    'RTO Initiated', 'RTO Delivered' => 'bg-red-100 text-red-800 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                {{ $shipment->status }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 font-bold">
                            @if($shipment->status === 'Delivered' && $shipment->is_cod)
                                @if($shipment->payment_type === 'UPI')
                                    <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-[10px] font-bold"><i class="fa-solid fa-qrcode mr-1"></i> UPI</span>
                                @else
                                    <span class="bg-green-50 text-green-700 px-2.5 py-1 rounded-lg text-[10px] font-bold"><i class="fa-solid fa-money-bill-wave mr-1"></i> Cash</span>
                                @endif
                            @else
                                <span class="text-gray-400 text-[10px]">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-300"></i>
                            <p class="text-sm font-bold text-gray-600">No parcels handed over for delivery on {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: Pickups Received at Hub -->
    <div x-show="activeTab === 'pickups'" class="bg-white rounded-b-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-extrabold text-gray-900 text-sm">
                <i class="fa-solid fa-arrow-down-to-line text-purple-600 mr-2"></i> Pickups Collected by Rider & Handed Into Hub Custody
            </h3>
            <span class="text-xs text-gray-500 font-bold">{{ $receivedPickups->count() }} Pickups</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3.5">Received Time</th>
                        <th class="px-5 py-3.5">AWB Number</th>
                        <th class="px-5 py-3.5">Seller / Store</th>
                        <th class="px-5 py-3.5">Pickup Address</th>
                        <th class="px-5 py-3.5">Destination</th>
                        <th class="px-5 py-3.5">Weight</th>
                        <th class="px-5 py-3.5">Hub Custody Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($receivedPickups as $pickup)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5 font-bold text-gray-500">
                            {{ $pickup->updated_at ? $pickup->updated_at->format('h:i A') : 'N/A' }}
                        </td>
                        <td class="px-5 py-3.5 font-black text-purple-700">
                            {{ $pickup->awb_number }}
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="font-bold text-gray-900">{{ !empty(trim($pickup->pickup_name ?? '')) ? $pickup->pickup_name : (optional($pickup->user)->company_name ?? optional($pickup->user)->name ?? 'Client Store') }}</div>
                            <div class="text-[10px] text-gray-500">{{ !empty(trim($pickup->pickup_phone ?? '')) ? $pickup->pickup_phone : optional($pickup->user)->phone }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 max-w-xs truncate" title="{{ $pickup->pickup_address }}">
                            {{ !empty(trim($pickup->pickup_address ?? '')) ? $pickup->pickup_address . (!empty($pickup->pickup_city) ? ', ' . $pickup->pickup_city : '') . (!empty($pickup->pickup_pincode) ? ' (' . $pickup->pickup_pincode . ')' : '') : 'Registered Pickup Location' }}
                        </td>
                        <td class="px-5 py-3.5 font-medium text-gray-800">
                            {{ $pickup->delivery_city }} ({{ $pickup->delivery_pincode }})
                        </td>
                        <td class="px-5 py-3.5 font-bold text-gray-900">
                            {{ $pickup->weight_kg }} KG
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                In Hub Custody ({{ $pickup->status }})
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-truck-pickup text-4xl mb-3 text-gray-300"></i>
                            <p class="text-sm font-bold text-gray-600">No pickups deposited at hub by this rider on {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: Undelivered / NDR Returns Received -->
    <div x-show="activeTab === 'returns'" class="bg-white rounded-b-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-extrabold text-gray-900 text-sm">
                <i class="fa-solid fa-rotate-left text-red-600 mr-2"></i> Undelivered Packages Returned to Hub (NDR / RTO Inventory)
            </h3>
            <span class="text-xs text-gray-500 font-bold">{{ $returnedToHub->count() }} Returned</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3.5">Attempt Time</th>
                        <th class="px-5 py-3.5">AWB Number</th>
                        <th class="px-5 py-3.5">Customer Name & Phone</th>
                        <th class="px-5 py-3.5">Delivery Address</th>
                        <th class="px-5 py-3.5">NDR Reason Reported</th>
                        <th class="px-5 py-3.5">Hub Action / Verification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($returnedToHub as $ret)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5 font-bold text-gray-500">
                            {{ $ret->updated_at ? $ret->updated_at->format('h:i A') : 'N/A' }}
                        </td>
                        <td class="px-5 py-3.5 font-black text-red-700">
                            {{ $ret->awb_number }}
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="font-bold text-gray-900">{{ $ret->receiver_name }}</div>
                            <div class="text-[10px] text-gray-500">{{ $ret->receiver_phone }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 max-w-xs truncate">
                            {{ $ret->delivery_address }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                {{ $ret->ndr_reason ?: 'Customer Not Available / Door Locked' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                Returned & Handed to Hub
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-circle-check text-4xl mb-3 text-emerald-400"></i>
                            <p class="text-sm font-bold text-gray-600">All dispatched parcels delivered or zero returns on this date!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: Cash Reconciliation -->
    <div x-show="activeTab === 'cash'" class="bg-white rounded-b-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-extrabold text-gray-900 text-sm">
                <i class="fa-solid fa-cash-register text-green-600 mr-2"></i> Cash on Delivery (COD) Reconciliation Log
            </h3>
            <span class="text-xs text-gray-900 font-extrabold bg-green-100 border border-green-200 px-3 py-1 rounded-full">
                Total Cash to Collect: &#8377;{{ number_format($totalCash, 2) }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-400 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3.5">Delivery Time</th>
                        <th class="px-5 py-3.5">AWB Number</th>
                        <th class="px-5 py-3.5">Customer Name</th>
                        <th class="px-5 py-3.5">Payment Method</th>
                        <th class="px-5 py-3.5">Collected Amount</th>
                        <th class="px-5 py-3.5">Hub Settlement Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($deliveredShipments->where('is_cod', 1) as $c)
                    @php
                        $isCash = empty($c->payment_type) || strcasecmp($c->payment_type, 'Cash') === 0;
                        $amt = $c->cod_amount > 0 ? $c->cod_amount : $c->invoice_value;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5 font-bold text-gray-500">
                            {{ $c->updated_at ? $c->updated_at->format('h:i A') : 'N/A' }}
                        </td>
                        <td class="px-5 py-3.5 font-black text-indigo-700">
                            {{ $c->awb_number }}
                        </td>
                        <td class="px-5 py-3.5 font-bold text-gray-900">
                            {{ $c->receiver_name }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if($isCash)
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-green-100 text-green-800">
                                    <i class="fa-solid fa-money-bill-wave mr-1"></i> Hard Cash
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800">
                                    <i class="fa-solid fa-qrcode mr-1"></i> UPI
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 font-black text-base {{ $isCash ? 'text-green-700' : 'text-blue-700' }}">
                            &#8377;{{ number_format($amt, 2) }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if($isCash)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    Deposit at Hub Cashier
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    Settled Directly to Escrow
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-wallet text-4xl mb-3 text-gray-300"></i>
                            <p class="text-sm font-bold text-gray-600">No COD collections for this date.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
