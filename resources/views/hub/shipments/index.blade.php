@extends('layouts.hub')
@section('title', 'Orders - OneStall Cargo')

@section('content')
<div class="space-y-6" x-data="shipmentTable()">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Orders</h1>
        </div>
        
        <div class="flex items-center gap-3 relative" x-data="{ showAddDropdown: false }">
            <a href="{{ route('hub.bulk') }}" class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-file-csv text-gray-400"></i> Bulk Order Update
            </a>
            
            <!-- Add Order Dropdown -->
            <div class="relative">
                <button @click="showAddDropdown = !showAddDropdown" class="px-5 py-2.5 bg-[#1e1b4b] hover:bg-black text-white text-xs font-bold rounded-xl shadow-md transition flex items-center gap-2">
                    Add Order <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </button>
                <div x-show="showAddDropdown" @click.away="showAddDropdown = false" class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-xl shadow-xl py-2 z-30" style="display: none;">
                    <a href="{{ route('hub.shipments.create') }}" class="block px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"><i class="fa-solid fa-plus text-blue-600 mr-2"></i> Single Order</a>
                    <a href="{{ route('hub.bulk') }}" class="block px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"><i class="fa-solid fa-file-excel text-green-600 mr-2"></i> Bulk CSV Upload</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card Container -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-visible">
        
        <!-- Status Tabs (URL Query Preserving) -->
        @php
            $currentStatus = request('status', 'new');
            if (!isset($counts) || empty($counts)) {
                $userShipments = \App\Models\Shipment::where('user_id', Auth::id());
                $counts = [
                    'new' => (clone $userShipments)->whereIn('status', ['new', 'New', 'Manifested', 'manifested', 'Booked', 'booked', 'pending', 'new order'])->count(),
                    'pickups' => (clone $userShipments)->where(function($q) {
                        $q->where('status', 'LIKE', 'pickup%')->orWhere('status', 'LIKE', 'Pickup%');
                    })->count(),
                    'transit' => (clone $userShipments)->where(function($q) {
                        $q->whereIn('status', ['in_transit', 'In Transit', 'transit', 'Transit', 'In-Transit', 'received', 'Received', 'out_for_delivery', 'Out for Delivery', 'ofd', 'OFD'])
                          ->orWhere('status', 'LIKE', 'out for delivery%')->orWhere('status', 'LIKE', 'Out for Delivery%');
                    })->count(),
                    'delivered' => (clone $userShipments)->whereIn('status', ['delivered', 'Delivered', 'DELIVERED'])->count(),
                    'rto' => (clone $userShipments)->where(function($q) {
                        $q->where('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%');
                    })->count(),
                    'cancelled' => (clone $userShipments)->where(function($q) {
                        $q->where('status', 'LIKE', 'cancel%')->orWhere('status', 'LIKE', 'Cancel%');
                    })->count(),
                    'all' => (clone $userShipments)->count(),
                ];
            }

            $kycRecord = \App\Models\Kyc::where('user_id', Auth::id())->first();
            $isKycApproved = $kycRecord && $kycRecord->status === 'approved';

            if (!function_exists('getTabUrl')) {
                function getTabUrl($statusVal) {
                    $params = request()->query();
                    $params['status'] = $statusVal;
                    unset($params['page']);
                    return '?' . http_build_query($params);
                }
            }
        @endphp

        <div class="flex border-b border-gray-100 px-4 pt-2 overflow-x-auto whitespace-nowrap bg-white">
            <a href="{{ getTabUrl('new') }}" class="{{ $currentStatus === 'new' ? 'text-[#4338ca] border-b-2 border-[#4338ca] font-bold' : 'text-gray-500 font-medium hover:text-gray-700' }} px-5 py-3 text-xs transition flex items-center gap-1.5">
                New <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded-full font-extrabold text-[10px]">{{ $counts['new'] }}</span>
            </a>
            <a href="{{ getTabUrl('pickups') }}" class="{{ $currentStatus === 'pickups' ? 'text-[#4338ca] border-b-2 border-[#4338ca] font-bold' : 'text-gray-500 font-medium hover:text-gray-700' }} px-5 py-3 text-xs transition flex items-center gap-1.5">
                Pickups <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">{{ $counts['pickups'] }}</span>
            </a>
            <a href="{{ getTabUrl('transit') }}" class="{{ $currentStatus === 'transit' ? 'text-[#4338ca] border-b-2 border-[#4338ca] font-bold' : 'text-gray-500 font-medium hover:text-gray-700' }} px-5 py-3 text-xs transition flex items-center gap-1.5">
                In Transit <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">{{ $counts['transit'] }}</span>
            </a>
            <a href="{{ getTabUrl('delivered') }}" class="{{ $currentStatus === 'delivered' ? 'text-[#4338ca] border-b-2 border-[#4338ca] font-bold' : 'text-gray-500 font-medium hover:text-gray-700' }} px-5 py-3 text-xs transition flex items-center gap-1.5">
                Delivered <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">{{ $counts['delivered'] }}</span>
            </a>
            <a href="{{ getTabUrl('rto') }}" class="{{ $currentStatus === 'rto' ? 'text-[#4338ca] border-b-2 border-[#4338ca] font-bold' : 'text-gray-500 font-medium hover:text-gray-700' }} px-5 py-3 text-xs transition flex items-center gap-1.5">
                RTO <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full font-extrabold text-[10px]">{{ $counts['rto'] }}</span>
            </a>
            <a href="{{ getTabUrl('cancelled') }}" class="{{ $currentStatus === 'cancelled' ? 'text-[#4338ca] border-b-2 border-[#4338ca] font-bold' : 'text-gray-500 font-medium hover:text-gray-700' }} px-5 py-3 text-xs transition flex items-center gap-1.5">
                Cancelled <span class="px-1.5 py-0.5 bg-rose-50 text-rose-700 rounded-full font-extrabold text-[10px]">{{ $counts['cancelled'] }}</span>
            </a>
            <a href="{{ getTabUrl('all') }}" class="{{ $currentStatus === 'all' ? 'text-[#4338ca] border-b-2 border-[#4338ca] font-bold' : 'text-gray-500 font-medium hover:text-gray-700' }} px-5 py-3 text-xs transition flex items-center gap-1.5">
                All
            </a>
        </div>

        <!-- Single Line Compact Filters Row with Date-Wise Presets & Pickers -->
        <form action="{{ route('hub.shipments') }}" method="GET" class="p-3 bg-[#f8fafc] border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <input type="hidden" name="status" value="{{ request('status', 'new') }}">
            
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Quick Date Dropdown -->
                <select name="quick_date" onchange="if(this.value && this.value !== 'custom'){ document.getElementById('hubStartDate').value=''; document.getElementById('hubEndDate').value=''; } this.form.submit()" class="bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-gray-700 outline-none">
                    <option value="">Date Range (All)</option>
                    <option value="today" {{ request('quick_date') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ request('quick_date') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="7days" {{ request('quick_date') === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30days" {{ request('quick_date') === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="this_month" {{ request('quick_date') === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ (request('start_date') || request('end_date')) && !request('quick_date') ? 'selected' : '' }}>Custom Dates</option>
                </select>

                <!-- From & To Date Pickers -->
                <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-xl px-2.5 py-1 text-xs shadow-xs">
                    <span class="text-gray-400 font-bold text-[10px] uppercase">From</span>
                    <input type="date" name="start_date" id="hubStartDate" value="{{ request('start_date') }}" class="font-semibold text-gray-700 outline-none text-xs bg-transparent">
                    <span class="text-gray-300">|</span>
                    <span class="text-gray-400 font-bold text-[10px] uppercase">To</span>
                    <input type="date" name="end_date" id="hubEndDate" value="{{ request('end_date') }}" class="font-semibold text-gray-700 outline-none text-xs bg-transparent">
                </div>

                <!-- Courier Partner Filter -->
                <select name="courier" onchange="this.form.submit()" class="bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-gray-700 outline-none">
                    <option value="all">Courier (All)</option>
                    <option value="OneStall Cargo" {{ request('courier') === 'OneStall Cargo' ? 'selected' : '' }}>OneStall Cargo</option>
                    @if(isset($couriers))
                        @foreach($couriers as $c)
                            @if($c !== 'OneStall Cargo')
                                <option value="{{ $c }}" {{ request('courier') === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endif
                        @endforeach
                    @endif
                </select>

                <!-- Payment Mode Filter -->
                <select name="payment_mode" onchange="this.form.submit()" class="bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-gray-700 outline-none">
                    <option value="">Payment (All)</option>
                    <option value="prepaid" {{ request('payment_mode') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                    <option value="cod" {{ request('payment_mode') === 'cod' ? 'selected' : '' }}>COD</option>
                </select>

                <!-- Search Input -->
                <div class="relative flex items-center">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Order ID / AWB / Mobile" class="bg-white border border-gray-200 rounded-xl pl-2.5 pr-7 py-1.5 text-xs font-semibold text-gray-700 outline-none w-44 sm:w-52 focus:border-[#4338ca]">
                    <button type="submit" class="absolute right-2 text-gray-400 hover:text-[#4338ca]"><i class="fa-solid fa-magnifying-glass text-[10px]"></i></button>
                </div>

                <!-- Filter Submit Button -->
                <button type="submit" class="px-3 py-1.5 bg-[#1e1b4b] hover:bg-black text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-filter text-[10px]"></i> Filter
                </button>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if(request('start_date') || request('end_date') || request('quick_date') || request('search') || (request('courier') && request('courier') !== 'all') || request('payment_mode'))
                    <a href="{{ route('hub.shipments', ['status' => request('status', 'new')]) }}" class="px-3 py-1.5 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-700 flex items-center gap-1.5 hover:bg-rose-100 shadow-xs">
                        <i class="fa-solid fa-rotate-right text-rose-500"></i> Clear Filter
                    </a>
                @endif
                <button type="button" onclick="window.print()" class="px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-700 flex items-center gap-1.5 hover:bg-gray-50 shadow-xs">
                    <i class="fa-solid fa-download text-gray-400"></i> Export
                </button>
            </div>
        </form>

        <!-- Clean Table View with STICKY Action Column & Working Checkboxes -->
        <div class="overflow-x-auto min-h-[300px] pb-28 relative">
            
            <!-- Floating Bulk Selection Bar -->
            <div x-show="selectedIds.length > 0" x-transition class="sticky top-0 z-30 bg-[#1e1b4b] text-white px-4 py-2.5 flex flex-col sm:flex-row items-center justify-between text-xs font-bold shadow-lg gap-2 sm:gap-0 rounded-xl mb-3">
                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                    <span class="bg-[#4338ca] px-2.5 py-0.5 rounded-full text-[11px]" x-text="selectedIds.length + ' selected'"></span>
                    <span>Selected Orders Actions:</span>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-start flex-wrap">
                    <!-- Bulk Print Slips (opens in new tab) -->
                    <form action="{{ route('hub.shipments.bulk-print') }}" method="POST" target="_blank" class="inline">
                        @csrf
                        <input type="hidden" name="ids" :value="selectedIds.join(',')">
                        <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                            <i class="fa-solid fa-print text-[11px]"></i>
                            <span>Print Slips (<span x-text="selectedIds.length"></span>)</span>
                        </button>
                    </form>

                    <!-- Bulk Download PDF -->
                    <form action="{{ route('hub.shipments.bulk-print') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="ids" :value="selectedIds.join(',')">
                        <input type="hidden" name="download" value="pdf">
                        <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                            <i class="fa-solid fa-file-pdf text-[11px]"></i>
                            <span>Download PDF</span>
                        </button>
                    </form>

                    <!-- Bulk Cancel -->
                    <form action="{{ route('hub.shipments.bulk-cancel') }}" method="POST" onsubmit="return confirm('Cancel selected orders? Amount will be refunded to wallet.');" class="inline">
                        @csrf
                        <input type="hidden" name="ids" :value="selectedIds.join(',')">
                        <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                            <i class="fa-solid fa-ban text-[11px]"></i>
                            <span>Bulk Cancel</span>
                        </button>
                    </form>

                    <button type="button" @click="selectedIds = []" class="text-gray-300 hover:text-white text-xs underline ml-1">Deselect All</button>
                </div>
            </div>

            <div class="overflow-x-auto w-full">
<table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="bg-[#f8fafc] text-[10px] font-extrabold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                        <th class="px-3 py-3 w-8"><input type="checkbox" @change="toggleAll($event)" :checked="isAllSelected()" class="rounded border-gray-300 accent-[#4338ca] cursor-pointer"></th>
                        <th class="px-3 py-3">ORDER / AWB DETAILS</th>
                        <th class="px-3 py-3">STATUS</th>
                        <th class="px-3 py-3">SHIPPING ADDRESS</th>
                        <th class="px-3 py-3">PRODUCT DETAILS</th>
                        <th class="px-3 py-3">PACKAGE DETAILS</th>
                        <th class="px-3 py-3">ORDER VALUE & MODE</th>
                        <th class="px-3 py-3">PICKUP LOCATION</th>
                        <!-- STICKY ACTION COLUMN HEADER -->
                        <th class="px-3 py-3 text-right sticky right-0 bg-[#f8fafc] z-20 shadow-[-3px_0_6px_rgba(0,0,0,0.04)]">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                    @forelse($shipments as $shipment)
                        <tr class="group hover:bg-gray-50/80 transition-colors">
                            <td class="px-3 py-4 align-top">
                                <input type="checkbox" value="{{ $shipment->id }}" x-model="selectedIds" class="rounded border-gray-300 accent-[#4338ca] cursor-pointer">
                            </td>

                            <!-- ORDER DETAILS -->
                            <td class="px-3 py-4 align-top">
                                <a href="#" @click.prevent="openOrderDetails({ 
    awb_number: '{{ $shipment->awb_number }}', 
    status: '{{ $shipment->status }}', 
    receiver_name: '{{ addslashes($shipment->receiver_name) }}', 
    receiver_phone: '{{ $shipment->receiver_phone }}', 
    delivery_address: '{{ addslashes($shipment->delivery_address) }}', 
    delivery_city: '{{ addslashes($shipment->delivery_city) }}', 
    delivery_pincode: '{{ $shipment->delivery_pincode }}', 
    invoice_value: {{ $shipment->invoice_value ?? 0 }}, 
    total_amount: {{ $shipment->total_amount ?? 0 }}, 
    cod_amount: {{ $shipment->cod_amount ?? 0 }},
    weight_kg: {{ $shipment->weight_kg ?? 0.5 }}, 
    is_cod: {{ $shipment->is_cod ? 1 : 0 }}, 
    courier_partner: '{{ $shipment->courier_partner }}', 
    shipment_type: '{{ $shipment->shipment_type }}',
    product_name: '{{ addslashes($shipment->product_name ?: "Package Item") }}',
    product_sku: '{{ addslashes($shipment->product_sku ?: "N/A") }}',
    product_qty: {{ $shipment->product_qty ?? 1 }}
})" class="font-bold text-[#4338ca] text-xs hover:underline block flex items-center gap-1.5">
    {{ $shipment->awb_number }} <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-gray-400"></i>
</a>
                                <div class="text-[10px] text-gray-500 mt-1">Synced On ? {{ $shipment->created_at->format('d Sep Y | h:i A') }}</div>
                                <div class="text-[10px] text-gray-500">Created On ? {{ $shipment->created_at->format('d Sep Y | h:i A') }}</div>
                                <div class="text-[10px] text-gray-400 font-semibold flex items-center gap-1 mt-1.5">
                                    <i class="fa-solid fa-desktop text-[9px]"></i> Manual
                                </div>
                            </td>

                            <!-- STATUS -->
                            <td class="px-3 py-4 align-top">
                                @php
                                    $st = strtolower(trim($shipment->status));
                                @endphp
                                @if(in_array($st, ['new', 'manifested', 'booked', 'pending', 'new order']))
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
                                @elseif(str_starts_with($st, 'pickup') || in_array($st, ['pickup_scheduled', 'pickups', 'pickup pending']))
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
                                @elseif(in_array($st, ['out_for_delivery', 'out for delivery', 'ofd']))
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-cyan-50 text-cyan-700 border border-cyan-200">Out for Delivery</span>
                                @elseif(in_array($st, ['in_transit', 'transit', 'in transit', 'in-transit', 'received']))
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-purple-50 text-purple-700 border border-purple-200">In Transit</span>
                                @elseif($st === 'delivered')
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Delivered</span>
                                @elseif(str_starts_with($st, 'rto') || in_array($st, ['rto', 'rto delivered', 'rto initiated', 'rto in-transit', 'rto in transit']))
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-orange-50 text-orange-700 border border-orange-200">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
                                @elseif(in_array($st, ['ndr', 'action_required', 'action required', 'undelivered']))
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">NDR</span>
                                @elseif(in_array($st, ['lost', 'damaged']))
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-red-50 text-red-700 border border-red-200">{{ ucfirst($shipment->status) }}</span>
                                @elseif(str_starts_with($st, 'cancel') || $st === 'cancelled')
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">Cancelled</span>
                                @else
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-gray-50 text-gray-700 border border-gray-200">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
                                @endif
                            </td>

                            <!-- SHIPPING ADDRESS -->
                            <td class="px-3 py-4 align-top max-w-[200px]">
                                <div class="font-bold text-gray-900 text-xs truncate">{{ $shipment->receiver_name }}</div>
                                <div class="text-[11px] text-gray-500 truncate mt-0.5">{{ $shipment->delivery_address }}</div>
                                <div class="text-[11px] text-gray-500 truncate">{{ $shipment->delivery_city }}, {{ $shipment->delivery_pincode }}</div>
                                <div class="text-[11px] text-gray-400 mt-1"><i class="fa-solid fa-phone text-[9px] mr-1"></i>{{ $shipment->receiver_phone }}</div>
                            </td>

                            <!-- PRODUCT DETAILS -->
                            <td class="px-3 py-4 align-top max-w-[180px]">
                                <div class="font-semibold text-gray-800 text-xs truncate">{{ $shipment->product_name ?: "Package Item" }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">QTY: {{ $shipment->product_qty ?? 1 }} ? SKU: {{ $shipment->product_sku ?: "N/A" }}</div>
                            </td>

                            <!-- PACKAGE DETAILS -->
                            <td class="px-3 py-4 align-top">
                                <div class="text-xs font-bold text-gray-800">{{ number_format($shipment->weight_kg, 2) }} kg</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">0.00 cm x 0.00 cm x 0.00 cm</div>
                            </td>

                            <!-- ORDER VALUE -->
                            <td class="px-3 py-4 align-top">
                                <div class="font-bold text-gray-900 text-xs">&#8377;{{ number_format($shipment->invoice_value > 0 ? $shipment->invoice_value : $shipment->total_amount, 2) }}</div>
                                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded {{ $shipment->is_cod ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                        {{ $shipment->is_cod ? 'COD' : 'Prepaid' }}
                                    </span>
                                    {{-- @if(($shipment->invoice_value ?? 0) > 50000)
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-600 border border-rose-200">Add E-Way Bill</span>
                                    @endif --}}
                                </div>
                            </td>

                            <!-- PICKUP ADDRESS -->
                            <td class="px-3 py-4 align-top">
                                <div class="text-xs font-semibold text-gray-700 truncate max-w-[100px]">{{ Auth::user()->company_name ?? 'Main Hub' }}</div>
                            </td>

                            <!-- STICKY ACTION COLUMN CELL -->
                            <td class="px-3 py-4 align-top text-right sticky right-0 bg-white group-hover:bg-gray-50 shadow-[-3px_0_6px_rgba(0,0,0,0.04)]" :class="openMenu ? 'z-[999]' : 'z-10'" x-data="{ openMenu: false }">
                                <div class="flex items-center justify-end gap-2">
                                    @if($isKycApproved)
                                        @php
                                            $stLower = strtolower(trim($shipment->status));
                                            $isCancellable = in_array($stLower, ['new', 'manifested', 'booked', 'pickup_scheduled', 'pickup scheduled', 'pending', 'new order', 'pickup pending', 'pickup_pending']) || str_starts_with($stLower, 'pickup');
                                        @endphp
                                        @if(in_array($stLower, ['new', 'manifested', 'booked', 'pending', 'new order']))
                                            <button type="button" @click.prevent="openCourierSelection({
                                                id: {{ $shipment->id }},
                                                awb_number: '{{ $shipment->awb_number }}',
                                                weight_kg: {{ $shipment->weight_kg ?? 0.5 }},
                                                length_cm: {{ $shipment->length_cm ?? 10 }},
                                                width_cm: {{ $shipment->width_cm ?? 10 }},
                                                height_cm: {{ $shipment->height_cm ?? 10 }},
                                                pincode: '{{ $shipment->delivery_pincode }}'
                                            })" class="px-3 py-1.5 bg-[#1e1b4b] hover:bg-black text-white text-xs font-bold rounded-lg shadow-sm transition">
                                                Ship Now
                                            </button>
                                        @elseif(str_starts_with($stLower, 'cancel') || $stLower === 'cancelled')
                                            <span class="px-3 py-1.5 bg-rose-50 text-rose-700 text-xs font-bold rounded-lg shadow-sm border border-rose-200 inline-flex items-center">
                                                <i class="fa-solid fa-ban mr-1"></i> Cancelled
                                            </span>
                                        @elseif($stLower === 'delivered')
                                            <span class="px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg shadow-sm border border-emerald-200 inline-flex items-center">
                                                <i class="fa-solid fa-circle-check mr-1"></i> Delivered
                                            </span>
                                        @elseif(str_starts_with($stLower, 'rto') || in_array($stLower, ['rto', 'rto delivered', 'rto initiated', 'rto in-transit', 'rto in transit']))
                                            <span class="px-3 py-1.5 bg-orange-50 text-orange-700 text-xs font-bold rounded-lg shadow-sm border border-orange-200 inline-flex items-center">
                                                <i class="fa-solid fa-rotate-left mr-1"></i> {{ ucwords(str_replace('_', ' ', $shipment->status)) }}
                                            </span>
                                        @elseif(in_array($stLower, ['out_for_delivery', 'out for delivery', 'ofd']))
                                            <span class="px-2.5 py-1.5 bg-cyan-50 text-cyan-700 text-xs font-bold rounded-lg shadow-sm border border-cyan-200 inline-flex items-center">
                                                <i class="fa-solid fa-motorcycle mr-1"></i> Out for Delivery
                                            </span>
                                        @elseif(in_array($stLower, ['ndr', 'action_required', 'action required', 'undelivered']))
                                            <span class="px-2.5 py-1.5 bg-rose-50 text-rose-700 text-xs font-bold rounded-lg shadow-sm border border-rose-200 inline-flex items-center">
                                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> NDR
                                            </span>
                                        @elseif(str_starts_with($stLower, 'pickup') || in_array($stLower, ['pickup_scheduled', 'pickup scheduled', 'pickups', 'pickup_assigned', 'pickup pending', 'pickup_pending']))
                                            <span class="px-2.5 py-1.5 bg-amber-50 text-amber-700 text-xs font-bold rounded-lg shadow-sm border border-amber-200 inline-flex items-center">
                                                <i class="fa-solid fa-truck-pickup mr-1"></i> {{ ucwords(str_replace('_', ' ', $shipment->status)) }}
                                            </span>
                                        @else
                                            <button type="button" disabled class="px-3 py-1.5 bg-purple-50 text-purple-800 text-xs font-bold rounded-lg shadow-sm cursor-not-allowed border border-purple-200">
                                                <i class="fa-solid fa-truck mr-1"></i> In Transit
                                            </button>
                                        @endif
                                    @else
                                        <a href="{{ route('hub.profile') }}?view=kyc" onclick="alert('KYC Verification Required!\n\nPlease complete and get your KYC approved before shipping orders.');" class="px-3 py-1.5 bg-gray-400 hover:bg-gray-600 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                            Ship Now
                                        </a>
                                    @endif

                                    {{-- Quick Direct Cancel Button --}}
                                    @if($isCancellable)
                                        <form action="{{ route('hub.shipments.cancel', $shipment->id) }}" method="POST" onsubmit="return confirm('Cancel order {{ $shipment->awb_number }}? Amount will be refunded to wallet.');" class="inline">
                                            @csrf
                                            <button type="submit" title="Cancel Order" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white rounded-lg border border-red-200 text-xs font-bold shadow-sm transition inline-flex items-center gap-1">
                                                <i class="fa-solid fa-ban text-[11px]"></i>
                                                <span>Cancel</span>
                                            </button>
                                        </form>
                                    @endif

                                    <button @click="openMenu = !openMenu" class="w-7 h-7 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center text-gray-600 text-xs shadow-sm">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>

                                    <!-- Dropdown Menu Opens Downwards smoothly -->
                                    <div x-show="openMenu" @click.away="openMenu = false" class="absolute right-2 top-10 w-52 min-w-[210px] bg-white border border-gray-200 rounded-xl shadow-2xl py-2 z-[9999] text-left" style="display: none;">
                                        <a href="{{ route('hub.label', $shipment->awb_number) }}" target="_blank" class="block px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50"><i class="fa-solid fa-print text-blue-500 mr-1.5"></i> Print / Download Slip</a>
                                        @if($isKycApproved)
                                            <a href="{{ route('hub.shipments.create') }}?edit={{ $shipment->id }}" class="block px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Edit Order</a>
                                        @else
                                            <a href="{{ route('hub.profile') }}?view=kyc" onclick="alert('KYC Verification Required!\n\nPlease complete and get your KYC approved.');" class="block px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Edit Order</a>
                                        @endif

                                        @if($isCancellable)
                                        <form action="{{ route('hub.shipments.cancel', $shipment->id) }}" method="POST" onsubmit="return confirm('Cancel this order?');">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 flex items-center gap-1.5"><i class="fa-solid fa-ban"></i> Cancel Order</button>
                                        </form>
                                        @endif
                                        <a href="{{ route('hub.shipments.create') }}?clone={{ $shipment->id }}" class="block px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Clone Order</a>
                                        <a href="{{ route('hub.invoice', $shipment->awb_number) }}" target="_blank" class="block px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Download Invoice</a>
                                        {{-- <a href="#" @click.prevent="openEway('{{ $shipment->awb_number }}'); openMenu = false" class="block px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Update E-way Bill</a> --}}
                                        <a href="#" @click.prevent="openComm('{{ $shipment->awb_number }}'); openMenu = false" class="block px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">View Communication</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-20 text-center">
                                <div class="text-gray-300 mb-2">
                                    <i class="fa-solid fa-box-archive text-4xl"></i>
                                </div>
                                <p class="text-sm font-bold text-gray-400">No orders found matching the filter</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
</div>
        </div>

        <!-- Pagination Bar -->
        @if($shipments->hasPages())
        <div class="p-4 border-t border-gray-100 bg-[#f8fafc]">
            {{ $shipments->links() }}
        </div>
        @endif
    </div>

        <!-- Order Details Modal -->
    <div x-show="showOrderModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showOrderModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="showOrderModal = false"></div>
            
            <div x-show="showOrderModal" x-transition class="relative inline-block w-full max-w-2xl p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-4">
                    <div>
                        <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                            <h3 class="text-xl font-black text-gray-900 tracking-tight" x-text="activeOrder.awb_number || 'Order Details'"></h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                                  :class="{
                                      'bg-blue-50 text-blue-700 border border-blue-200': ['new','manifested','booked'].includes((activeOrder.status||'').toLowerCase()),
                                      'bg-amber-50 text-amber-700 border border-amber-200': ['pickup_scheduled','pickups'].includes((activeOrder.status||'').toLowerCase()),
                                      'bg-purple-50 text-purple-700 border border-purple-200': ['in_transit','transit'].includes((activeOrder.status||'').toLowerCase()),
                                      'bg-emerald-50 text-emerald-700 border border-emerald-200': (activeOrder.status||'').toLowerCase() === 'delivered',
                                      'bg-rose-50 text-rose-700 border border-rose-200': (activeOrder.status||'').toLowerCase() === 'cancelled'
                                  }"
                                  x-text="activeOrder.status || 'Manifested'">
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Courier Partner: <span class="font-bold text-gray-800" x-text="activeOrder.courier_partner || 'Delhivery'"></span></p>
                    </div>
                    <button @click="showOrderModal = false" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Modal Body Grid (3 Sections) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    
                    <!-- Customer Details -->
                    <div class="p-4 rounded-2xl border border-gray-100 bg-[#f8fafc]">
                        <div class="font-bold text-gray-400 text-[10px] uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-user text-[#4338ca]"></i> Recipient Details
                        </div>
                        <div class="font-bold text-gray-900 text-sm mb-1" x-text="activeOrder.receiver_name || 'N/A'"></div>
                        <div class="text-gray-600 leading-relaxed" x-text="activeOrder.delivery_address || 'N/A'"></div>
                        <div class="text-gray-600 font-semibold mt-1" x-text="(activeOrder.delivery_city || '') + ', ' + (activeOrder.delivery_pincode || '')"></div>
                        <div class="text-gray-500 mt-2 font-mono"><i class="fa-solid fa-phone text-gray-400 mr-1"></i> <span x-text="activeOrder.receiver_phone || 'N/A'"></span></div>
                    </div>

                    <!-- Product Details -->
                    <div class="p-4 rounded-2xl border border-gray-100 bg-[#f8fafc]">
                        <div class="font-bold text-gray-400 text-[10px] uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-box-open text-[#4338ca]"></i> Product Details
                        </div>
                        <div class="font-bold text-gray-900 text-sm mb-1" x-text="activeOrder.product_name || 'Package Item'"></div>
                        <div class="space-y-1 mt-2 text-gray-600">
                            <div class="flex justify-between"><span>Quantity:</span> <span class="font-bold text-gray-800" x-text="activeOrder.product_qty || 1"></span></div>
                            <div class="flex justify-between"><span>SKU:</span> <span class="font-bold text-gray-800" x-text="activeOrder.product_sku || 'N/A'"></span></div>
                        </div>
                    </div>

                    <!-- Financial & Shipment Info -->
                    <div class="p-4 rounded-2xl border border-gray-100 bg-[#f8fafc]">
                        <div class="font-bold text-gray-400 text-[10px] uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-indian-rupee-sign text-[#4338ca]"></i> Payment & Weight
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center border-b border-gray-200/60 pb-1.5">
                                <span class="text-gray-500">Invoice Value</span>
                                <span class="font-bold text-gray-900" x-html="'<i class=''fa-solid fa-indian-rupee-sign text-[10px]''></i> ' + (activeOrder.invoice_value || 0)"></span>
                            </div>
                                                        <div class="flex justify-between items-center border-b border-gray-200/60 pb-1.5" x-show="activeOrder.is_cod">
                                <span class="text-gray-500">COD Collect</span>
                                <span class="font-bold text-gray-900" x-html="'<i class=''fa-solid fa-indian-rupee-sign text-[10px]''></i> ' + (activeOrder.cod_amount || activeOrder.invoice_value || 0)"></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200/60 pb-1.5">
                                <span class="text-gray-500">Payment Mode</span>
                                <span class="font-bold px-2 py-0.5 rounded text-[10px]" :class="activeOrder.is_cod ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'" x-text="activeOrder.is_cod ? 'COD' : 'Prepaid'"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Total Weight</span>
                                <span class="font-bold text-gray-900" x-text="(activeOrder.weight_kg || 0.5) + ' kg'"></span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Action Buttons -->
                <div class="flex justify-end items-center gap-2 mt-6 pt-4 border-t border-gray-100">
                    <button @click="showOrderModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition">Close</button>

                    <template x-if="(activeOrder.status || '').toLowerCase() === 'cancelled'">
                        <span class="px-4 py-2 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-1.5">
                            <i class="fa-solid fa-ban text-rose-500"></i> Order Cancelled
                        </span>
                    </template>
                    <template x-if="['new', 'manifested', 'booked', 'pickup scheduled', 'pickup_scheduled', 'pending', 'new order'].includes((activeOrder.status || '').toLowerCase())">
                        <form :action="'{{ url('hub/shipments') }}/' + activeOrder.id + '/cancel'" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? Freight charge will be refunded to wallet.');" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition flex items-center gap-1.5 shadow-md">
                                <i class="fa-solid fa-ban"></i> Cancel Order
                            </button>
                        </form>
                    </template>
                    <template x-if="(activeOrder.status || '').toLowerCase() !== 'cancelled'">
                        <a :href="'{{ url('hub/shipments/label') }}/' + activeOrder.awb_number" target="_blank" class="px-4 py-2 text-xs font-bold text-white bg-[#1e1b4b] hover:bg-black rounded-xl transition flex items-center gap-1.5 shadow-md">
                            <i class="fa-solid fa-print"></i> Print / Download Slip
                        </a>
                    </template>
                </div>

            </div>
        </div>
    </div>

    {{-- Update E-Way Bill Modal
    <div x-show="showEwayModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showEwayModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="showEwayModal = false"></div>
            
            <div x-show="showEwayModal" x-transition class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-extrabold text-gray-900">Update E-Way Bill</h3>
                    <button @click="showEwayModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <form action="{{ route('hub.shipments.eway') }}" method="POST">
                    @csrf
                    <input type="hidden" name="awb_number" :value="currentAwb">
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 mb-3">Updating E-Way Bill for AWB: <span class="font-bold text-[#4338ca]" x-text="currentAwb"></span></p>
                        <label class="block text-xs font-bold text-gray-700 mb-1">E-Way Bill Number <span class="text-red-500">*</span></label>
                        <input type="text" name="eway_bill_number" x-model="ewayNumber" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4338ca] focus:ring-1 focus:ring-[#4338ca]" placeholder="e.g., 123456789012">
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showEwayModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-[#4338ca] rounded-lg hover:bg-indigo-700 transition">Save E-Way Bill</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    --}}

    <!-- View Communication Modal -->
    <div x-show="showCommModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showCommModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="showCommModal = false"></div>
            
            <div x-show="showCommModal" x-transition class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-extrabold text-gray-900">Communication Logs</h3>
                    <button @click="showCommModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-xs text-gray-500 mb-6">Tracking updates sent for AWB: <span class="font-bold text-[#4338ca]" x-text="currentAwb"></span></p>
                    
                    <div class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:ml-[2.5rem] md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-gray-200 before:to-transparent">
                        <div class="relative flex items-center justify-between md:justify-normal">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-green-100 text-green-500 shadow-sm shrink-0 relative z-10 md:ml-5">
                                <i class="fa-brands fa-whatsapp text-lg"></i>
                            </div>
                            <div class="w-[calc(100%-4rem)] md:w-[calc(100%-5rem)] ml-4 p-4 rounded-xl border border-gray-100 bg-white shadow-sm">
                                <div class="flex items-center justify-between space-x-2 mb-1">
                                    <div class="font-bold text-gray-900 text-xs">WhatsApp Sent</div>
                                    <time class="font-medium text-[10px] text-gray-500">Today, 10:30 AM</time>
                                </div>
                                <div class="text-[11px] text-gray-600 mt-2">Your order has been shipped and is on the way. Tracking Link: ...</div>
                            </div>
                        </div>
                        
                        <div class="relative flex items-center justify-between md:justify-normal">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-blue-100 text-blue-500 shadow-sm shrink-0 relative z-10 md:ml-5">
                                <i class="fa-solid fa-envelope text-sm"></i>
                            </div>
                            <div class="w-[calc(100%-4rem)] md:w-[calc(100%-5rem)] ml-4 p-4 rounded-xl border border-gray-100 bg-white shadow-sm">
                                <div class="flex items-center justify-between space-x-2 mb-1">
                                    <div class="font-bold text-gray-900 text-xs">Email Sent</div>
                                    <time class="font-medium text-[10px] text-gray-500">Yesterday, 5:45 PM</time>
                                </div>
                                <div class="text-[11px] text-gray-600 mt-2">Order confirmed. We are packing your items...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- COURIER SELECTION MODAL -->
    <div x-show="showCourierModal" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showCourierModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="showCourierModal = false"></div>
            
            <div x-show="showCourierModal" x-transition class="relative inline-block w-full max-w-4xl p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 tracking-tight">Select Courier for <span class="text-indigo-700" x-text="activeShipment.awb_number"></span></h3>
                        <div class="text-xs text-gray-500 font-semibold mt-1">
                            Dead Wt: <span x-text="activeShipment.weight_kg"></span>kg | Vol Wt: <span x-text="volumetricWeight()"></span>kg
                            <span class="ml-2 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md font-bold">Charged Wt: <span x-text="chargeableWeight()"></span>kg</span>
                        </div>
                    </div>
                    <button @click="showCourierModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full p-2 transition">
                        <i class="fa-solid fa-xmark text-lg w-5 h-5 flex items-center justify-center"></i>
                    </button>
                </div>

                <!-- Courier List -->
                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                    <template x-if="isCalculating">
                        <div class="py-10 text-center">
                            <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 text-3xl mb-3"></i>
                            <p class="text-sm font-bold text-gray-500">Fetching live courier rates...</p>
                        </div>
                    </template>

                    <template x-if="!isCalculating">
                        <div>
                            <template x-for="courier in courierRates" :key="courier.name">
                                <div class="flex items-center justify-between p-4 border rounded-xl mb-3 transition" :class="selectedCourier.name === courier.name ? 'border-indigo-600 bg-indigo-50/30 shadow-md ring-1 ring-indigo-600' : 'border-gray-200 hover:border-indigo-300 hover:shadow-sm bg-white'">
                                    
                                    <div class="flex items-center gap-4 cursor-pointer flex-1" @click="selectedCourier = courier">
                                        <div class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0" :class="selectedCourier.name === courier.name ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                            <div class="w-2 h-2 rounded-full bg-white" x-show="selectedCourier.name === courier.name"></div>
                                        </div>
                                        
                                        <!-- Courier Logo -->
                                        <div class="w-12 h-12 rounded bg-white border border-gray-100 flex items-center justify-center p-1 shrink-0 overflow-hidden shadow-sm">
                                            <img :src="getLogo(courier.name)" :alt="courier.name" class="w-full h-full object-contain" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?background=ffffff&color=000&name=' + encodeURIComponent(this.alt);">
                                        </div>

                                        <div>
                                            <div class="font-black text-gray-900 text-base flex items-center gap-2">
                                                <span x-text="courier.name"></span>
                                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] rounded uppercase tracking-wider" x-show="courier.recommended">Recommended</span>
                                            </div>
                                            <div class="text-xs font-semibold mt-1 flex items-center gap-2">
                                                <span :class="(courier.transport_mode || '').toLowerCase() === 'air' ? 'text-blue-600 bg-blue-50 px-2 py-0.5 rounded flex items-center gap-1' : 'text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded flex items-center gap-1'">
                                                    <i class="fa-solid" :class="(courier.transport_mode || '').toLowerCase() === 'air' ? 'fa-plane' : 'fa-truck-fast'"></i>
                                                    <span x-text="courier.transport_mode || 'Surface'"></span>
                                                </span>
                                                <span class="text-gray-400">|</span>
                                                <span class="text-gray-500">Est. Delivery: <span x-text="courier.eta"></span></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-2xl font-black text-gray-900">&#8377;<span x-text="courier.rate"></span></div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase mt-0.5">Shipping Fee</div>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Footer Action -->
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-5 border-t border-gray-100">
                    <div>
                        <div class="text-sm font-bold text-gray-600 flex items-center gap-2">
                            <i class="fa-solid fa-wallet text-amber-500"></i>
                            Wallet Balance: <span class="text-gray-900">&#8377;{{ number_format(Auth::user()->wallet_balance ?? 0, 2) }}</span>
                            <a href="{{ route('hub.wallet.index') }}" target="_blank" class="text-xs text-indigo-600 underline font-extrabold hover:text-indigo-800 ml-1">+ Recharge</a>
                        </div>
                        <template x-if="selectedCourier.rate && parseFloat(selectedCourier.rate) > {{ (float)(Auth::user()->wallet_balance ?? 0) }}">
                            <div class="text-[11px] font-bold text-rose-600 mt-1 flex items-center gap-1">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Insufficient balance! Need &#8377;<span x-text="(parseFloat(selectedCourier.rate) - {{ (float)(Auth::user()->wallet_balance ?? 0) }}).toFixed(2)"></span> more.</span>
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <button @click="showCourierModal = false" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition shadow-sm">Cancel</button>
                        
                        <form :action="`{{ url('hub/shipments') }}/${activeShipment.id}/ship-now`" method="POST" @submit="isSubmitting = true">
                            @csrf
                            <input type="hidden" name="courier_partner" :value="selectedCourier.name">
                            <input type="hidden" name="shipping_charge" :value="selectedCourier.rate">
                            
                            <template x-if="selectedCourier.rate && parseFloat(selectedCourier.rate) > {{ (float)(Auth::user()->wallet_balance ?? 0) }}">
                                <a href="{{ route('hub.wallet.index') }}" target="_blank" class="px-5 py-2.5 text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md transition flex items-center gap-2">
                                    <i class="fa-solid fa-wallet"></i> Recharge to Ship
                                </a>
                            </template>

                            <template x-if="!selectedCourier.rate || parseFloat(selectedCourier.rate) <= {{ (float)(Auth::user()->wallet_balance ?? 0) }}">
                                <button type="submit" :disabled="!selectedCourier.name || isSubmitting" class="px-5 py-2.5 text-sm font-bold text-white rounded-xl shadow-md transition flex items-center gap-2" :class="(!selectedCourier.name || isSubmitting) ? 'bg-gray-400 cursor-not-allowed' : 'bg-[#1e1b4b] hover:bg-black'">
                                    <span x-show="!isSubmitting"><i class="fa-solid fa-paper-plane mr-1.5"></i> Confirm & Ship Order</span>
                                    <span x-show="isSubmitting"><i class="fa-solid fa-circle-notch fa-spin"></i> Processing...</span>
                                </button>
                            </template>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('shipmentTable', () => ({
            selectedIds: [],
            allRowIds: [{{ $shipments->pluck('id')->implode(',') }}],
            showOrderModal: false, 
            activeOrder: {}, 
            openOrderDetails(order) { this.activeOrder = order; this.showOrderModal = true; }, 
            showEwayModal: false,
            showCommModal: false,
            currentAwb: '',
            ewayNumber: '',
            
            showCourierModal: false,
            activeShipment: {},
            courierRates: [],
            selectedCourier: {},
            isCalculating: false,
            isSubmitting: false,
            
            openCourierSelection(shipment) {
                this.activeShipment = shipment;
                this.selectedCourier = {};
                this.showCourierModal = true;
                this.isCalculating = true;
                
                fetch(`/hub/shipments/${shipment.id}/couriers`)
                    .then(res => res.json())
                    .then(data => {
                        this.courierRates = data;
                        if(data.length > 0) {
                            let recommended = data.find(c => c.recommended);
                            this.selectedCourier = recommended || data[0];
                        }
                        this.isCalculating = false;
                    })
                    .catch(err => {
                        console.error('Error fetching couriers', err);
                        this.isCalculating = false;
                    });
            },
            
            volumetricWeight() {
                if(!this.activeShipment.length_cm) return 0;
                return ((this.activeShipment.length_cm * this.activeShipment.width_cm * this.activeShipment.height_cm) / 5000).toFixed(2);
            },
            
            chargeableWeight() {
                let vWt = parseFloat(this.volumetricWeight());
                let dWt = parseFloat(this.activeShipment.weight_kg || 0);
                let max = Math.max(vWt, dWt);
                return (Math.ceil(max * 2) / 2).toFixed(2);
            },
            
            getLogo(name) {
                let n = (name || '').toLowerCase();
                if (n.includes('xpressbees')) return 'https://ship.onestallcargo.com/images/xpressbees.png';
                if (n.includes('delhivery')) return 'https://upload.wikimedia.org/wikipedia/commons/9/90/Delhivery_Logo.png';
                if (n.includes('blue dart')) return 'https://upload.wikimedia.org/wikipedia/commons/2/23/Blue_Dart_logo.png';
                if (n.includes('ekart')) return 'https://upload.wikimedia.org/wikipedia/commons/6/65/Ekart_Logo.png';
                if (n.includes('ecom express')) return 'https://upload.wikimedia.org/wikipedia/commons/3/30/Ecom_Express_Logo.png';
                if (n.includes('india post')) return 'https://upload.wikimedia.org/wikipedia/commons/e/ec/India_Post_Logo.png';
                if (n.includes('shadowfax')) return 'https://shadowfax.in/images/shadowfax-logo.svg';
                if (n.includes('onestall')) return 'https://ui-avatars.com/api/?background=1e1b4b&color=fff&name=OS';
                return 'https://ui-avatars.com/api/?background=f3f4f6&color=374151&name=' + encodeURIComponent(name);
            },
            
            toggleAll(e) {
                if (e.target.checked) {
                    this.selectedIds = [...this.allRowIds];
                } else {
                    this.selectedIds = [];
                }
            },
            
            isAllSelected() {
                return this.allRowIds.length > 0 && this.selectedIds.length === this.allRowIds.length;
            },

            openEway(awb) {
                this.currentAwb = awb;
                this.ewayNumber = '';
                this.showEwayModal = true;
            },
            
            submitEway() {
                if(!this.ewayNumber) {
                    alert('Please enter an E-way bill number');
                    return;
                }
                alert('E-Way Bill ' + this.ewayNumber + ' successfully updated for ' + this.currentAwb);
                this.showEwayModal = false;
            },
            
            openComm(awb) {
                this.currentAwb = awb;
                this.showCommModal = true;
            }
        }));
    });
</script>
@endsection


