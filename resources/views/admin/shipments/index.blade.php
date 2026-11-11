@extends('layouts.admin')

@section('title', 'Shipments - OneStall Cargo')

@section('content')
<div class="space-y-6" x-data="adminShipments()">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Shipments Management</h1>
            <p class="text-sm text-gray-500 mt-1">View and manage all B2B and B2C shipments</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.shipments.create') }}" class="px-4 py-2 bg-[var(--gold)] text-gray-900 font-bold rounded-xl shadow-sm hover:bg-yellow-500 transition text-sm flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Book Shipment
            </a>
            <a href="{{ route('admin.reports.export') }}" class="btn btn-secondary text-sm flex items-center gap-2"><i class="fa-solid fa-download"></i> Export CSV</a>
        </div>
    </div>

    <!-- Filters -->
    <form action="{{ route('admin.shipments.index') }}" method="GET" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-bold text-gray-700 mb-1">Search AWB / Seller</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="AWB or Seller Name" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-[var(--gold)]">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Quick Date</label>
            <select name="quick_date" onchange="if(this.value && this.value !== 'custom'){ document.getElementById('adminStartDate').value=''; document.getElementById('adminEndDate').value=''; } this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-[var(--gold)] bg-white">
                <option value="">Date Range (All)</option>
                <option value="today" {{ request('quick_date') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ request('quick_date') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="7days" {{ request('quick_date') === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30days" {{ request('quick_date') === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                <option value="this_month" {{ request('quick_date') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="custom" {{ (request('start_date') || request('end_date')) && !request('quick_date') ? 'selected' : '' }}>Custom Dates</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">From Date</label>
            <input type="date" name="start_date" id="adminStartDate" value="{{ request('start_date') }}" class="px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-[var(--gold)] bg-white">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">To Date</label>
            <input type="date" name="end_date" id="adminEndDate" value="{{ request('end_date') }}" class="px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-[var(--gold)] bg-white">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-[var(--gold)] bg-white">
                <option value="">All Statuses</option>
                <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Pickup Scheduled" {{ request('status') == 'Pickup Scheduled' ? 'selected' : '' }}>Pickup Scheduled</option>
                <option value="Manifested" {{ request('status') == 'Manifested' ? 'selected' : '' }}>Manifested</option>
                <option value="In Transit" {{ request('status') == 'In Transit' ? 'selected' : '' }}>In Transit</option>
                <option value="Out for Delivery" {{ request('status') == 'Out for Delivery' ? 'selected' : '' }}>Out for Delivery</option>
                <option value="Delivered" {{ request('status') == 'Delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="NDR" {{ request('status') == 'NDR' ? 'selected' : '' }}>NDR</option>
                <option value="RTO" {{ request('status') == 'RTO' ? 'selected' : '' }}>RTO</option>
                <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        @if(isset($couriers))
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Courier</label>
            <select name="courier" class="px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-[var(--gold)] bg-white">
                <option value="all">All Couriers</option>
                <option value="OneStall Cargo" {{ request('courier') === 'OneStall Cargo' ? 'selected' : '' }}>OneStall Cargo</option>
                @foreach($couriers as $c)
                    @if($c !== 'OneStall Cargo')
                        <option value="{{ $c }}" {{ request('courier') === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        @endif

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white font-bold rounded-lg text-xs hover:bg-black transition flex items-center gap-1.5">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            @if(request('search') || request('status') || request('start_date') || request('end_date') || request('quick_date') || (request('courier') && request('courier') !== 'all'))
                <a href="{{ route('admin.shipments.index') }}" class="px-3 py-2 bg-rose-50 text-rose-700 border border-rose-200 font-bold rounded-lg text-xs hover:bg-rose-100 transition flex items-center gap-1">
                    <i class="fa-solid fa-rotate-right"></i> Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Data Table -->
    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Floating Bulk Selection Bar -->
        <div x-show="selectedIds.length > 0" x-transition class="bg-[#1e1b4b] text-white px-5 py-3 flex flex-col sm:flex-row items-center justify-between text-xs font-bold shadow-lg gap-2 sm:gap-0 border-b border-indigo-950">
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                <span class="bg-[#4338ca] px-3 py-1 rounded-full text-[11px]" x-text="selectedIds.length + ' selected'"></span>
                <span>Selected Orders Actions:</span>
            </div>
            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-between sm:justify-start flex-wrap">
                <!-- Bulk Print Slips -->
                <form action="{{ route('admin.shipments.bulk-print') }}" method="POST" target="_blank" class="inline">
                    @csrf
                    <input type="hidden" name="ids" :value="selectedIds.join(',')">
                    <button type="submit" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-print text-[11px]"></i>
                        <span>Print Slips (<span x-text="selectedIds.length"></span>)</span>
                    </button>
                </form>

                <!-- Bulk Download PDF -->
                <form action="{{ route('admin.shipments.bulk-print') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="ids" :value="selectedIds.join(',')">
                    <input type="hidden" name="download" value="pdf">
                    <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-file-pdf text-[11px]"></i>
                        <span>Download PDF</span>
                    </button>
                </form>

                <button type="button" @click="selectedIds = []" class="text-gray-300 hover:text-white text-xs underline ml-2">Deselect All</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                        <th class="px-4 py-4 w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="isAllSelected()" class="rounded border-gray-300 accent-[#1e1b4b] cursor-pointer">
                        </th>
                        <th class="px-6 py-4">AWB Number</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Seller</th>
                        <th class="px-6 py-4">Destination</th>
                        <th class="px-6 py-4">Weight</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-gray-700 font-medium">
                    @forelse($shipments as $shipment)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-4">
                                <input type="checkbox" value="{{ $shipment->id }}" x-model="selectedIds" class="rounded border-gray-300 accent-[#1e1b4b] cursor-pointer">
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-[var(--gold-deep)]">{{ $shipment->awb_number }}</span>
                            </td>
                            <td class="px-6 py-4">{{ $shipment->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-6 py-4">{{ $shipment->user ? $shipment->user->name : 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $shipment->delivery_city }} ({{ $shipment->delivery_pincode }})</td>
                            <td class="px-6 py-4">{{ $shipment->weight_kg }} kg</td>
                            <td class="px-6 py-4 font-bold text-gray-900">₹{{ number_format($shipment->total_amount, 2) }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $st = strtolower(trim($shipment->status));
                                    if (in_array($st, ['new', 'manifested', 'booked', 'pending', 'new order'])) {
                                        $color = 'bg-blue-50 text-blue-700 border border-blue-200';
                                    } elseif (str_starts_with($st, 'pickup')) {
                                        $color = 'bg-amber-50 text-amber-700 border border-amber-200';
                                    } elseif (in_array($st, ['out_for_delivery', 'out for delivery', 'ofd'])) {
                                        $color = 'bg-cyan-50 text-cyan-700 border border-cyan-200';
                                    } elseif (in_array($st, ['in_transit', 'transit', 'in transit', 'in-transit', 'received'])) {
                                        $color = 'bg-purple-50 text-purple-700 border border-purple-200';
                                    } elseif ($st === 'delivered') {
                                        $color = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                    } elseif (str_starts_with($st, 'rto')) {
                                        $color = 'bg-orange-50 text-orange-700 border border-orange-200';
                                    } elseif (in_array($st, ['ndr', 'action_required', 'undelivered'])) {
                                        $color = 'bg-rose-50 text-rose-700 border border-rose-200';
                                    } elseif (str_starts_with($st, 'cancel')) {
                                        $color = 'bg-red-50 text-red-700 border border-red-200';
                                    } else {
                                        $color = 'bg-gray-100 text-gray-800 border border-gray-200';
                                    }
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $color }}">
                                    {{ $shipment->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.shipments.label', $shipment->awb_number) }}" target="_blank" title="Print / Download Order Slip" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-200 transition shadow-xs">
                                        <i class="fa-solid fa-print"></i> Slip
                                    </a>
                                    <a href="{{ route('admin.shipments.invoice', $shipment->awb_number) }}" target="_blank" title="Download Tax Invoice" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs font-semibold bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200 transition">
                                        <i class="fa-solid fa-file-invoice text-gray-500"></i>
                                    </a>
                                    <button type="button" @click.prevent='openOrderDetails({ 
                                        awb_number: "{{ $shipment->awb_number }}", 
                                        status: "{{ $shipment->status }}", 
                                        seller_name: @json($shipment->user ? ($shipment->user->company_name ?: $shipment->user->name) : "N/A"),
                                        seller_email: @json($shipment->user ? $shipment->user->email : ""),
                                        pickup_name: @json(!empty(trim($shipment->pickup_name ?? '')) ? $shipment->pickup_name : (optional($shipment->user)->company_name ?: optional($shipment->user)->name ?: 'Primary Hub')),
                                        pickup_address: @json(!empty(trim($shipment->pickup_address ?? '')) ? $shipment->pickup_address : (optional($shipment->user)->company_address ?: 'Origin Address')),
                                        pickup_city: "{{ $shipment->pickup_city ?? '' }}",
                                        pickup_state: "{{ $shipment->pickup_state ?? '' }}",
                                        pickup_pincode: "{{ $shipment->pickup_pincode ?? '' }}",
                                        pickup_phone: "{{ $shipment->pickup_phone ?? optional($shipment->user)->phone ?? '' }}",
                                        receiver_name: "{{ $shipment->receiver_name }}", 
                                        receiver_phone: "{{ $shipment->receiver_phone }}", 
                                        delivery_address: @json($shipment->delivery_address), 
                                        delivery_city: "{{ $shipment->delivery_city }}", 
                                        delivery_pincode: "{{ $shipment->delivery_pincode }}", 
                                        invoice_value: {{ $shipment->invoice_value ?? 0 }}, 
                                        total_amount: {{ $shipment->total_amount ?? 0 }}, 
                                        cod_amount: {{ $shipment->cod_amount ?? 0 }}, 
                                        weight_kg: {{ $shipment->weight_kg ?? 0.5 }}, 
                                        is_cod: {{ $shipment->is_cod ? 1 : 0 }}, 
                                        courier_partner: "{{ $shipment->courier_partner ?? "N/A" }}", 
                                        shipment_type: "{{ $shipment->shipment_type ?? "B2C" }}",
                                        product_name: @json($shipment->product_name ?? "Package Item"),
                                        product_sku: @json($shipment->product_sku ?? "N/A"),
                                        product_qty: {{ $shipment->product_qty ?? 1 }}
                                    })' title="View Shipment Details" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200 transition">
                                        <i class="fa-solid fa-eye text-gray-500"></i> Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-200"></i>
                                    <p>No shipments found in the system yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($shipments->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>
<!-- Order Details Modal (Injected) -->
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
                                      'bg-blue-50 text-blue-700 border border-blue-200': ['new','manifested','booked','pending','new order'].includes((activeOrder.status||'').toLowerCase()),
                                      'bg-amber-50 text-amber-700 border border-amber-200': (activeOrder.status||'').toLowerCase().startsWith('pickup'),
                                      'bg-cyan-50 text-cyan-700 border border-cyan-200': ['out_for_delivery','out for delivery','ofd'].includes((activeOrder.status||'').toLowerCase()),
                                      'bg-purple-50 text-purple-700 border border-purple-200': ['in_transit','transit','in transit','received'].includes((activeOrder.status||'').toLowerCase()),
                                      'bg-emerald-50 text-emerald-700 border border-emerald-200': (activeOrder.status||'').toLowerCase() === 'delivered',
                                      'bg-orange-50 text-orange-700 border border-orange-200': (activeOrder.status||'').toLowerCase().startsWith('rto'),
                                      'bg-rose-50 text-rose-700 border border-rose-200': ['ndr','action_required','undelivered'].includes((activeOrder.status||'').toLowerCase()) || (activeOrder.status||'').toLowerCase().startsWith('cancel')
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

                <!-- Modal Body Grid (4 Sections) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    
                    <!-- Pickup Details -->
                    <div class="p-4 rounded-2xl border border-gray-100 bg-[#f8fafc]">
                        <div class="font-bold text-gray-400 text-[10px] uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-warehouse text-[#4338ca]"></i> Pickup Details
                        </div>
                        <div class="font-bold text-gray-900 text-sm mb-1" x-text="activeOrder.pickup_name || 'Primary Hub'"></div>
                        <div class="text-gray-600 leading-relaxed" x-text="activeOrder.pickup_address || 'Origin Address'"></div>
                        <div class="text-gray-600 font-semibold mt-1" x-text="(activeOrder.pickup_city || '') + (activeOrder.pickup_state ? ', ' + activeOrder.pickup_state : '') + (activeOrder.pickup_pincode ? ' - ' + activeOrder.pickup_pincode : '')"></div>
                        <div class="text-gray-500 mt-2 font-mono" x-show="activeOrder.pickup_phone"><i class="fa-solid fa-phone text-gray-400 mr-1"></i> <span x-text="activeOrder.pickup_phone"></span></div>
                    </div>

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

                    <!-- Product & Seller Details -->
                    <div class="p-4 rounded-2xl border border-gray-100 bg-[#f8fafc]">
                        <div class="font-bold text-gray-400 text-[10px] uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-box-open text-[#4338ca]"></i> Item & Merchant
                        </div>
                        <div class="font-bold text-gray-900 text-sm mb-1" x-text="activeOrder.product_name || 'Package Item'"></div>
                        <div class="space-y-1 mt-2 text-gray-600">
                            <div class="flex justify-between"><span>Quantity:</span> <span class="font-bold text-gray-800" x-text="activeOrder.product_qty || 1"></span></div>
                            <div class="flex justify-between"><span>SKU:</span> <span class="font-bold text-gray-800" x-text="activeOrder.product_sku || 'N/A'"></span></div>
                            <div class="flex justify-between pt-1 border-t border-gray-200/60"><span>Merchant:</span> <span class="font-bold text-gray-800 truncate max-w-[120px]" x-text="activeOrder.seller_name || 'N/A'"></span></div>
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
                                <span class="font-bold text-gray-900" x-html="'&#8377;' + (activeOrder.invoice_value || 0)"></span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-200/60 pb-1.5" x-show="activeOrder.is_cod">
                                <span class="text-gray-500">COD Collect</span>
                                <span class="font-bold text-gray-900" x-html="'&#8377;' + (activeOrder.cod_amount || activeOrder.invoice_value || 0)"></span>
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
                    <template x-if="(activeOrder.status || '').toLowerCase() !== 'cancelled'">
                        <div class="flex items-center gap-2">
                            <a :href="`{{ url('admin/shipments') }}/${activeOrder.awb_number}/invoice`" target="_blank" class="px-3.5 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-200 rounded-xl transition flex items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice text-gray-500"></i> Invoice
                            </a>
                            <a :href="`{{ url('admin/shipments') }}/${activeOrder.awb_number}/lr`" target="_blank" class="px-3.5 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-200 rounded-xl transition flex items-center gap-1.5">
                                <i class="fa-solid fa-truck text-gray-500"></i> LR
                            </a>
                            <a :href="`{{ url('admin/shipments') }}/${activeOrder.awb_number}/label`" target="_blank" class="px-4 py-2 text-xs font-bold text-white bg-[#1e1b4b] hover:bg-black rounded-xl transition flex items-center gap-1.5 shadow-md">
                                <i class="fa-solid fa-print"></i> Print / Download Slip
                            </a>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>

        
</div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminShipments', () => ({
                showOrderModal: false, 
                activeOrder: {}, 
                selectedIds: [],
                allRowIds: [{{ $shipments->pluck('id')->implode(',') }}],
                openOrderDetails(order) { 
                    this.activeOrder = order; 
                    this.showOrderModal = true; 
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
                }
            }));
        });
    </script>

@endsection
