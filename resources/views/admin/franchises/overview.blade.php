@extends('layouts.admin')

@section('title', 'Franchise 360° - ' . $franchise->company_name)

@section('content')
<div class="space-y-6 max-w-[1400px] mx-auto">
    <!-- Breadcrumb & Top Bar -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start md:items-center gap-3.5">
            <a href="{{ route('admin.hubs.index') }}" class="w-8 h-8 rounded-lg bg-gray-50 hover:bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-500 hover:text-gray-900 transition text-xs shadow-sm shrink-0 mt-0.5 md:mt-0">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $franchise->company_name }}</h1>
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider {{ $franchise->isState() ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-blue-50 text-blue-700 border border-blue-100' }}">
                        <i class="fa-solid {{ $franchise->isState() ? 'fa-crown' : 'fa-map-pin' }} mr-1 text-[10px]"></i>
                        {{ strtoupper($franchise->franchise_level) }} FRANCHISE
                    </span>
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider {{ $franchise->status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                        {{ $franchise->status }}
                    </span>
                    <span class="font-mono text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200 font-semibold">
                        {{ $franchise->franchise_code ?? ('FR-' . str_pad($franchise->id, 4, '0', STR_PAD_LEFT)) }}
                    </span>
                </div>
                <div class="flex items-center gap-2.5 text-xs text-gray-500 mt-1 flex-wrap">
                    <span><i class="fa-solid fa-user mr-1 text-gray-400"></i> {{ $franchise->owner_name }} ({{ $franchise->phone }})</span>
                    <span class="text-gray-300">•</span>
                    <span><i class="fa-solid fa-envelope mr-1 text-gray-400"></i> {{ $franchise->email }}</span>
                    <span class="text-gray-300">•</span>
                    <span><i class="fa-solid fa-location-dot mr-1 text-gray-400"></i> {{ $franchise->city }}, {{ $franchise->state }}</span>
                    @if($franchise->isDistrict() && $franchise->parentFranchise)
                        <span class="text-gray-300">•</span>
                        <span>
                            Parent State: 
                            <a href="{{ route('admin.franchises.overview', $franchise->parentFranchise->id) }}" class="font-semibold text-blue-600 hover:underline">
                                {{ $franchise->parentFranchise->company_name }} ({{ $franchise->parentFranchise->state }})
                            </a>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.franchises.edit', $franchise->id) }}" class="px-3.5 py-2 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-sm transition inline-flex items-center gap-1.5">
                <i class="fa-solid fa-pen text-[10px] text-gray-400"></i> Edit Settings
            </a>
            <form action="{{ route('admin.franchises.toggle', $franchise->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-lg text-xs font-semibold {{ $franchise->status === 'approved' ? 'bg-white border border-rose-200 text-rose-600 hover:bg-rose-50' : 'bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-100' }} transition inline-flex items-center gap-1.5">
                    <i class="fa-solid {{ $franchise->status === 'approved' ? 'fa-ban' : 'fa-check' }} text-[10px]"></i>
                    {{ $franchise->status === 'approved' ? 'Suspend' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 font-semibold text-xs flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- 6 KPI Metric Cards (Matching Admin Dashboard style exactly) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- 1. Delivered -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <p class="text-[11px] font-bold text-gray-600">Delivered</p>
                <div class="w-7 h-7 rounded-full border border-emerald-100 flex items-center justify-center text-emerald-500 bg-emerald-50/50">
                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-3xl font-black text-gray-900">{{ number_format($metrics->delivered) }}</h3>
            </div>
            <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100">
                Successful dropoffs
            </p>
        </div>

        <!-- 2. Active Pipeline -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <p class="text-[11px] font-bold text-gray-600">Active Pipeline</p>
                <div class="w-7 h-7 rounded-full border border-blue-100 flex items-center justify-center text-blue-500 bg-blue-50/50">
                    <i class="fa-solid fa-truck-fast text-[10px]"></i>
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-3xl font-black text-gray-900">{{ number_format($metrics->pending) }}</h3>
            </div>
            <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100">
                In-transit / Out for dev
            </p>
        </div>

        <!-- 3. NDR / RTO -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <p class="text-[11px] font-bold text-gray-600">NDR / RTO</p>
                <div class="w-7 h-7 rounded-full border border-rose-100 flex items-center justify-center text-rose-500 bg-rose-50/50">
                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-3xl font-black text-gray-900">{{ number_format($metrics->ndr_rto) }}</h3>
            </div>
            <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100">
                Exceptions & returns
            </p>
        </div>

        <!-- 4. Commissions -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <p class="text-[11px] font-bold text-gray-600">Commissions</p>
                <div class="w-7 h-7 rounded-full border border-purple-100 flex items-center justify-center text-purple-600 bg-purple-50/50">
                    <i class="fa-solid fa-hand-holding-dollar text-[10px]"></i>
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-black text-gray-900">₹{{ number_format($metrics->commission_earned, 2) }}</h3>
            </div>
            <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100 truncate">
                Pending: <span class="text-gray-900">₹{{ number_format($metrics->commission_pending, 2) }}</span>
            </p>
        </div>

        <!-- 5. COD In Hand -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <p class="text-[11px] font-bold text-gray-600">COD In Hand</p>
                <div class="w-7 h-7 rounded-full border border-amber-100 flex items-center justify-center text-amber-500 bg-amber-50/50">
                    <i class="fa-solid fa-money-bill-wave text-[10px]"></i>
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-black text-rose-600">₹{{ number_format($metrics->pending_cod, 2) }}</h3>
            </div>
            <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100 truncate">
                Collected: <span class="text-gray-900">₹{{ number_format($metrics->cod_collected, 2) }}</span>
            </p>
        </div>

        <!-- 6. Wallet Balance -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <p class="text-[11px] font-bold text-gray-600">Wallet Balance</p>
                <div class="w-7 h-7 rounded-full border border-blue-100 flex items-center justify-center text-blue-600 bg-blue-50/50">
                    <i class="fa-solid fa-wallet text-[10px]"></i>
                </div>
            </div>
            <div class="my-3">
                <h3 class="text-2xl font-black text-gray-900">₹{{ number_format($metrics->wallet_balance, 2) }}</h3>
            </div>
            <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100">
                Available earnings
            </p>
        </div>
    </div>

    <!-- Navigation Tabs (Enterprise SaaS style) -->
    <div class="flex border-b border-gray-200 gap-6 text-xs font-bold overflow-x-auto scrollbar-hide">
        <a href="{{ route('admin.franchises.overview', ['id' => $franchise->id, 'tab' => 'overview']) }}" 
           class="pb-3 border-b-2 flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'overview' ? 'border-gray-900 text-gray-900 font-extrabold' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <i class="fa-solid fa-chart-pie text-gray-400"></i> Overview & Territory
        </a>
        <a href="{{ route('admin.franchises.overview', ['id' => $franchise->id, 'tab' => 'orders']) }}" 
           class="pb-3 border-b-2 flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'orders' ? 'border-gray-900 text-gray-900 font-extrabold' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <i class="fa-solid fa-box text-gray-400"></i> Shipments & Orders ({{ $metrics->total_shipments }})
        </a>
        <a href="{{ route('admin.franchises.overview', ['id' => $franchise->id, 'tab' => 'cod']) }}" 
           class="pb-3 border-b-2 flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'cod' ? 'border-gray-900 text-gray-900 font-extrabold' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <i class="fa-solid fa-coins text-gray-400"></i> COD Reconciliation
        </a>
        <a href="{{ route('admin.franchises.overview', ['id' => $franchise->id, 'tab' => 'commissions']) }}" 
           class="pb-3 border-b-2 flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'commissions' ? 'border-gray-900 text-gray-900 font-extrabold' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <i class="fa-solid fa-receipt text-gray-400"></i> Commission Ledger
        </a>
        <a href="{{ route('admin.franchises.overview', ['id' => $franchise->id, 'tab' => 'wallet']) }}" 
           class="pb-3 border-b-2 flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'wallet' ? 'border-gray-900 text-gray-900 font-extrabold' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <i class="fa-solid fa-wallet text-gray-400"></i> Wallet Transactions
        </a>
    </div>

    <!-- ==================== TAB 1: OVERVIEW & TERRITORY ==================== -->
    @if($activeTab === 'overview')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left 8 Columns -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Commercial & Commission Configuration Card -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">Commercial & Commission Settings</h3>
                        <a href="{{ route('admin.franchises.edit', $franchise->id) }}" class="text-xs font-semibold text-blue-600 hover:underline">
                            <i class="fa-solid fa-pen text-[10px] mr-1"></i> Edit Configuration
                        </a>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                        <div class="p-3.5 bg-gray-50/70 rounded-xl border border-gray-100">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Delivery Commission</div>
                            <div class="text-sm font-black text-emerald-700 mt-1">
                                @if($franchise->delivery_commission_type === 'flat')
                                    ₹{{ $franchise->delivery_commission_rate }} flat per shipment
                                @elseif($franchise->delivery_commission_type === 'per_kg')
                                    ₹{{ $franchise->delivery_commission_rate }}/kg
                                @elseif($franchise->delivery_commission_type === 'percent')
                                    {{ $franchise->delivery_commission_rate }}% of freight charge
                                @else
                                    Not Configured
                                @endif
                            </div>
                        </div>

                        <div class="p-3.5 bg-gray-50/70 rounded-xl border border-gray-100">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pickup Commission</div>
                            <div class="text-sm font-black text-blue-700 mt-1">
                                @if($franchise->pickup_commission_type === 'flat')
                                    ₹{{ $franchise->pickup_commission_rate }} flat per shipment
                                @elseif($franchise->pickup_commission_type === 'per_kg')
                                    ₹{{ $franchise->pickup_commission_rate }}/kg
                                @elseif($franchise->pickup_commission_type === 'percent')
                                    {{ $franchise->pickup_commission_rate }}% of freight charge
                                @else
                                    Not Configured
                                @endif
                            </div>
                        </div>

                        @if($franchise->isState())
                            <div class="p-3.5 bg-purple-50/40 rounded-xl border border-purple-100 sm:col-span-2">
                                <div class="text-[10px] font-bold text-purple-700 uppercase tracking-wider">State Linehaul Royalty</div>
                                <div class="text-sm font-black text-purple-900 mt-1">
                                    @if($franchise->state_royalty_type === 'flat')
                                        ₹{{ $franchise->state_royalty_rate }} flat royalty per shipment in state
                                    @elseif($franchise->state_royalty_type === 'per_kg')
                                        ₹{{ $franchise->state_royalty_rate }}/kg linehaul royalty
                                    @elseif($franchise->state_royalty_type === 'percent')
                                        {{ $franchise->state_royalty_rate }}% royalty on freight value
                                    @else
                                        Not Configured
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Subordinate District Hubs (for State Franchise) -->
                @if($franchise->isState())
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-gray-900 tracking-tight">
                                Subordinate District Hubs ({{ $franchise->districtFranchises->count() }})
                            </h3>
                            <a href="{{ route('admin.hubs.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
                                Network Directory &rarr;
                            </a>
                        </div>

                        @if($franchise->districtFranchises->isNotEmpty())
                            <div class="divide-y divide-gray-100">
                                @foreach($franchise->districtFranchises as $subDist)
                                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50/50 transition">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-gray-900">{{ $subDist->company_name }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $subDist->status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                                    {{ $subDist->status }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                District: <span class="font-semibold text-gray-800">{{ $subDist->district ?? 'N/A' }}</span> • 
                                                Manager: {{ $subDist->owner_name }} ({{ $subDist->phone }})
                                            </div>
                                            @php
                                                $subPins = is_array($subDist->serviceable_pincodes) ? $subDist->serviceable_pincodes : json_decode($subDist->serviceable_pincodes, true);
                                            @endphp
                                            @if(!empty($subPins) && is_array($subPins))
                                                <div class="text-[11px] text-gray-500 font-mono mt-1 flex flex-wrap gap-1 items-center">
                                                    <span class="text-gray-400 font-sans text-[10px] font-bold uppercase">PIN:</span>
                                                    @foreach(array_slice($subPins, 0, 6) as $sp)
                                                        <span class="bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded border border-gray-200 text-[10px]">{{ $sp }}</span>
                                                    @endforeach
                                                    @if(count($subPins) > 6)
                                                        <span class="text-gray-400 text-[10px] font-sans font-medium">+{{ count($subPins) - 6 }} more</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.franchises.overview', $subDist->id) }}" class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-xs transition inline-flex items-center gap-1 border border-blue-100">
                                                <i class="fa-solid fa-chart-pie text-[10px]"></i> View 360°
                                            </a>
                                            <a href="{{ route('admin.franchises.edit', $subDist->id) }}" class="px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold text-xs transition">
                                                Edit
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-400 text-xs">
                                <i class="fa-solid fa-map-location-dot text-2xl mb-1 text-gray-300"></i>
                                <p>No district franchises assigned to this state yet.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Recent Assigned Shipments Preview -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">Recent Assigned Shipments</h3>
                        <a href="{{ route('admin.franchises.overview', ['id' => $franchise->id, 'tab' => 'orders']) }}" class="text-xs font-semibold text-blue-600 hover:underline">
                            View All Shipments &rarr;
                        </a>
                    </div>

                    @if($shipments && $shipments->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs whitespace-nowrap">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-5 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">AWB NUMBER</th>
                                        <th class="px-5 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">RECIPIENT</th>
                                        <th class="px-5 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">STATUS</th>
                                        <th class="px-5 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">MODE / AMOUNT</th>
                                        <th class="px-5 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">DATE</th>
                                        <th class="px-5 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider text-right">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                                    @foreach($shipments->take(6) as $s)
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="px-5 py-3 font-mono font-bold text-gray-900">{{ $s->awb_number }}</td>
                                            <td class="px-5 py-3">
                                                <div class="font-semibold text-gray-900">{{ $s->receiver_name }}</div>
                                                <div class="text-[10px] text-gray-400">{{ $s->delivery_city }} ({{ $s->delivery_pincode }})</div>
                                            </td>
                                            <td class="px-5 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                                    {{ $s->status === 'Delivered' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($s->status === 'In Transit' ? 'bg-blue-50 text-blue-700 border border-blue-100' : ($s->status === 'NDR' ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-gray-100 text-gray-700 border border-gray-200')) }}">
                                                    {{ $s->status }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3">
                                                @if($s->is_cod || $s->payment_type === 'COD')
                                                    <span class="font-bold text-rose-600 font-mono text-xs">COD: ₹{{ number_format($s->cod_amount ?? $s->invoice_value ?? 0, 2) }}</span>
                                                @else
                                                    <span class="text-gray-500 font-medium">Prepaid: ₹{{ number_format($s->total_amount ?? 0, 2) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 text-gray-400">{{ $s->created_at ? $s->created_at->format('d M Y') : 'N/A' }}</td>
                                            <td class="px-5 py-3 text-right">
                                                <a href="{{ route('admin.shipments.slip', $s->awb_number) }}" target="_blank" class="px-2.5 py-1 rounded bg-gray-100 hover:bg-gray-200 font-semibold text-[11px] text-gray-700 inline-flex items-center gap-1 transition">
                                                    <i class="fa-solid fa-receipt text-[10px]"></i> Slip
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-400 text-xs">
                            No shipments assigned yet.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right 4 Columns: Balanced Territory & Account Panels -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Territory Scope Panel -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5">
                    <h3 class="text-sm font-bold text-gray-900 tracking-tight mb-3.5 pb-2.5 border-b border-gray-100">
                        Territory Scope
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold tracking-wider">State Jurisdiction</span>
                            <span class="font-bold text-gray-900 text-sm mt-0.5 block">{{ $franchise->state }}</span>
                        </div>
                        @if($franchise->isDistrict())
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold tracking-wider">Assigned District</span>
                                <span class="font-bold text-gray-900 text-sm mt-0.5 block">{{ $franchise->district ?? 'N/A' }}</span>
                                @php
                                    $fPins = is_array($franchise->serviceable_pincodes) ? $franchise->serviceable_pincodes : json_decode($franchise->serviceable_pincodes, true);
                                @endphp
                                @if(!empty($fPins) && is_array($fPins))
                                    <div class="text-xs text-gray-600 font-mono mt-1">
                                        <span class="text-gray-400 font-sans text-[10px] font-bold uppercase block">Pincodes:</span>
                                        {{ implode(', ', array_slice($fPins, 0, 8)) }}{{ count($fPins) > 8 ? ' +' . (count($fPins) - 8) . ' more' : '' }}
                                    </div>
                                @endif
                            </div>
                        @endif
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold tracking-wider">Operating Base Address</span>
                            <span class="font-medium text-gray-700 mt-0.5 block leading-relaxed">{{ $franchise->address }}, {{ $franchise->city }}</span>
                        </div>
                    </div>
                </div>

                <!-- Serviceable Pincodes Panel -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5">
                    <div class="flex items-center justify-between mb-3.5 pb-2.5 border-b border-gray-100">
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">
                            Serviceable Pincodes
                        </h3>
                        @php
                            $pins = is_array($franchise->serviceable_pincodes) ? $franchise->serviceable_pincodes : json_decode($franchise->serviceable_pincodes, true);
                            $pinCount = is_array($pins) ? count($pins) : 0;
                        @endphp
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                            {{ $pinCount }} Active
                        </span>
                    </div>

                    @if($franchise->isState())
                        <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100 text-purple-900 text-xs leading-relaxed mb-3">
                            <i class="fa-solid fa-circle-info mr-1 text-purple-600"></i>
                            As a State Franchise, routing encompasses all pincodes across subordinate district franchises in <strong>{{ $franchise->state }}</strong>.
                        </div>
                    @endif

                    @if(!empty($pins) && is_array($pins))
                        <div class="flex flex-wrap gap-1.5 max-h-56 overflow-y-auto pr-1">
                            @foreach($pins as $p)
                                <span class="font-mono text-[11px] font-semibold px-2 py-1 bg-gray-50 border border-gray-200 rounded text-gray-700">
                                    {{ $p }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400 text-center py-4">No specific pincodes assigned directly.</p>
                    @endif
                </div>

                <!-- Portal Account Panel -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5">
                    <h3 class="text-sm font-bold text-gray-900 tracking-tight mb-3.5 pb-2.5 border-b border-gray-100">
                        Portal Account Details
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-400">User ID:</span>
                            <span class="font-mono font-bold text-gray-800">#{{ $franchise->user_id }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-400">Login Role:</span>
                            <span class="font-semibold text-gray-800">{{ $franchise->user->role ?? 'franchise' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-400">Account Status:</span>
                            <span class="font-bold text-emerald-700 capitalize">{{ $franchise->user->status ?? 'active' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-400">Registered On:</span>
                            <span class="font-medium text-gray-600">{{ $franchise->created_at ? $franchise->created_at->format('d M Y') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ==================== TAB 2: SHIPMENTS & ORDERS ==================== -->
    @if($activeTab === 'orders')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-4 bg-gray-50/50 border-b border-gray-200">
                <form action="{{ route('admin.franchises.overview', $franchise->id) }}" method="GET" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                    <input type="hidden" name="tab" value="orders">
                    <div class="flex-1 flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by AWB, receiver name, phone, or city..." 
                                   class="w-full pl-8 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:border-blue-600 outline-none">
                        </div>
                        <div class="w-full sm:w-48">
                            <select name="status" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:border-blue-600 outline-none">
                                <option value="">All Statuses</option>
                                <option value="Delivered" {{ request('status') === 'Delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="In Transit" {{ request('status') === 'In Transit' ? 'selected' : '' }}>In Transit</option>
                                <option value="Out for Delivery" {{ request('status') === 'Out for Delivery' ? 'selected' : '' }}>Out for Delivery</option>
                                <option value="Manifested" {{ request('status') === 'Manifested' ? 'selected' : '' }}>Manifested</option>
                                <option value="NDR" {{ request('status') === 'NDR' ? 'selected' : '' }}>NDR</option>
                                <option value="RTO" {{ request('status') === 'RTO' ? 'selected' : '' }}>RTO</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition">
                            Apply Filter
                        </button>
                        <a href="{{ route('admin.franchises.overview', ['id' => $franchise->id, 'tab' => 'orders']) }}" class="px-3 py-2 rounded-lg bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 text-xs font-semibold shadow-sm transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Shipments Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">AWB & WAYBILL</th>
                            <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">SENDER / SHIPPER</th>
                            <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">RECIPIENT</th>
                            <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">ROUTE & PINCODE</th>
                            <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">PAYMENT & AMOUNT</th>
                            <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">CURRENT STATUS</th>
                            <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">CREATED AT</th>
                            <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                        @if($shipments && $shipments->isNotEmpty())
                            @foreach($shipments as $s)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-3.5">
                                        <div class="font-mono font-bold text-gray-900">{{ $s->awb_number }}</div>
                                        <div class="text-[10px] text-gray-400">Order: {{ $s->order_id ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <div class="font-semibold text-gray-900">{{ !empty(trim($s->pickup_name ?? '')) ? $s->pickup_name : (optional($s->user)->company_name ?: optional($s->user)->name ?: 'Merchant') }}</div>
                                        <div class="text-[10px] text-gray-400">{{ !empty($s->pickup_city) ? ($s->pickup_city . (!empty($s->pickup_pincode) ? ' (' . $s->pickup_pincode . ')' : '')) : 'Origin' }}</div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <div class="font-semibold text-gray-900">{{ $s->receiver_name }}</div>
                                        <div class="text-[10px] text-gray-500">{{ $s->receiver_phone }}</div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <div class="font-medium text-gray-900">{{ $s->delivery_city }}, {{ $s->delivery_state }}</div>
                                        <div class="font-mono text-[10px] text-gray-500">PIN: {{ $s->delivery_pincode }}</div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        @if($s->is_cod || $s->payment_type === 'COD')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                                COD: ₹{{ number_format($s->cod_amount ?? $s->invoice_value ?? 0, 2) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-50 text-gray-700 border border-gray-200">
                                                Prepaid: ₹{{ number_format($s->total_amount ?? 0, 2) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                            {{ $s->status === 'Delivered' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($s->status === 'In Transit' ? 'bg-blue-50 text-blue-700 border border-blue-100' : ($s->status === 'NDR' ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-gray-100 text-gray-700 border border-gray-200')) }}">
                                            {{ $s->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-gray-400">
                                        {{ $s->created_at ? $s->created_at->format('d M Y, h:i A') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-3.5 text-right">
                                        <a href="{{ route('admin.shipments.slip', $s->awb_number) }}" target="_blank" 
                                           class="px-2.5 py-1 rounded bg-gray-100 hover:bg-gray-200 font-semibold text-[11px] text-gray-700 inline-flex items-center gap-1 transition">
                                            <i class="fa-solid fa-receipt text-[10px]"></i> Slip
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="text-center py-10 text-gray-400">
                                    <i class="fa-solid fa-box-open text-2xl mb-1 text-gray-300"></i>
                                    <p>No shipments found matching the criteria.</p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            @if($shipments && $shipments->hasPages())
                <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50/50">
                    {{ $shipments->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- ==================== TAB 3: COD RECONCILIATION ==================== -->
    @if($activeTab === 'cod')
        <div class="space-y-6">
            <!-- COD Reconciliation Cards Banner (Admin Dashboard Style) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <p class="text-[11px] font-bold text-gray-600">Total COD Collected (Delivered)</p>
                        <div class="w-7 h-7 rounded-full border border-gray-100 flex items-center justify-center text-gray-400">
                            <i class="fa-solid fa-layer-group text-[10px]"></i>
                        </div>
                    </div>
                    <div class="my-3">
                        <h3 class="text-3xl font-black text-gray-900">₹{{ number_format($metrics->cod_collected, 2) }}</h3>
                    </div>
                    <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100">
                        From <span class="font-bold text-gray-900">{{ $metrics->cod_delivered_count }}</span> delivered cash orders
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <p class="text-[11px] font-bold text-gray-600">COD Remitted to Admin</p>
                        <div class="w-7 h-7 rounded-full border border-emerald-100 flex items-center justify-center text-emerald-500 bg-emerald-50/50">
                            <i class="fa-solid fa-building-columns text-[10px]"></i>
                        </div>
                    </div>
                    <div class="my-3">
                        <h3 class="text-3xl font-black text-emerald-700">₹{{ number_format($metrics->cod_remitted, 2) }}</h3>
                    </div>
                    <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100">
                        Settled & acknowledged in platform
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <p class="text-[11px] font-bold text-gray-600">Outstanding COD In-Hand</p>
                        <div class="w-7 h-7 rounded-full border border-rose-100 flex items-center justify-center text-rose-500 bg-rose-50/50">
                            <i class="fa-solid fa-clock text-[10px]"></i>
                        </div>
                    </div>
                    <div class="my-3">
                        <h3 class="text-3xl font-black text-rose-600">₹{{ number_format($metrics->pending_cod, 2) }}</h3>
                    </div>
                    <p class="text-[10px] font-semibold text-rose-600 pt-2 border-t border-gray-100">
                        Cash collected awaiting remittance
                    </p>
                </div>
            </div>

            <!-- COD Shipments Table with Filter -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">Delivered COD Consignments</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $metrics->cod_delivered_count }} consignments recorded</p>
                    </div>
                    <form action="{{ route('admin.franchises.overview', $franchise->id) }}" method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="tab" value="cod">
                        <select name="cod_status" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs font-semibold outline-none focus:border-blue-600">
                            <option value="">All COD Orders</option>
                            <option value="pending" {{ request('cod_status') === 'pending' ? 'selected' : '' }}>Outstanding / In-Hand Only</option>
                            <option value="remitted" {{ request('cod_status') === 'remitted' ? 'selected' : '' }}>Remitted to Admin Only</option>
                        </select>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">AWB NUMBER</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">RECIPIENT</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">DESTINATION</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">COD CASH AMOUNT</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">REMITTANCE STATUS</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">DELIVERED AT</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider text-right">SLIP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                            @if($codShipments && $codShipments->isNotEmpty())
                                @foreach($codShipments as $cs)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-3.5 font-mono font-bold text-gray-900">{{ $cs->awb_number }}</td>
                                        <td class="px-6 py-3.5">
                                            <div class="font-semibold text-gray-900">{{ $cs->receiver_name }}</div>
                                            <div class="text-[10px] text-gray-400">{{ $cs->receiver_phone }}</div>
                                        </td>
                                        <td class="px-6 py-3.5">{{ $cs->delivery_city }} ({{ $cs->delivery_pincode }})</td>
                                        <td class="px-6 py-3.5 font-bold text-rose-600 font-mono text-xs">
                                            ₹{{ number_format($cs->cod_amount ?? $cs->invoice_value ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-3.5">
                                            @if($cs->cod_remitted)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100 inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-check text-[9px]"></i> Remitted
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-100 inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-clock text-[9px]"></i> In Hand (Pending)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3.5 text-gray-400">
                                            {{ $cs->delivered_at ? \Carbon\Carbon::parse($cs->delivered_at)->format('d M Y, h:i A') : ($cs->updated_at ? $cs->updated_at->format('d M Y') : 'N/A') }}
                                        </td>
                                        <td class="px-6 py-3.5 text-right">
                                            <a href="{{ route('admin.shipments.slip', $cs->awb_number) }}" target="_blank" class="px-2.5 py-1 rounded bg-gray-100 hover:bg-gray-200 font-semibold text-[11px] text-gray-700 inline-flex items-center gap-1 transition">
                                                <i class="fa-solid fa-receipt text-[10px]"></i> Slip
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-gray-400">
                                        <i class="fa-solid fa-coins text-2xl mb-1 text-gray-300"></i>
                                        <p>No COD shipments found for this filter.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if($codShipments && $codShipments->hasPages())
                    <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50/50">
                        {{ $codShipments->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- ==================== TAB 4: COMMISSION LEDGER ==================== -->
    @if($activeTab === 'commissions')
        <div class="space-y-6">
            <!-- Commission Summary Banner -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <p class="text-[11px] font-bold text-gray-600">Total Credited Earnings</p>
                        <div class="w-7 h-7 rounded-full border border-purple-100 flex items-center justify-center text-purple-600 bg-purple-50/50">
                            <i class="fa-solid fa-hand-holding-dollar text-[10px]"></i>
                        </div>
                    </div>
                    <div class="my-3">
                        <h3 class="text-3xl font-black text-gray-900">₹{{ number_format($metrics->commission_earned, 2) }}</h3>
                    </div>
                    <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100">
                        From completed deliveries, pickups, and state royalties
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <p class="text-[11px] font-bold text-gray-600">Pending / Processing Commission</p>
                        <div class="w-7 h-7 rounded-full border border-gray-100 flex items-center justify-center text-gray-400">
                            <i class="fa-solid fa-clock text-[10px]"></i>
                        </div>
                    </div>
                    <div class="my-3">
                        <h3 class="text-3xl font-black text-gray-900">₹{{ number_format($metrics->commission_pending, 2) }}</h3>
                    </div>
                    <p class="text-[10px] font-semibold text-gray-500 pt-2 border-t border-gray-100">
                        Scheduled for next settlement cycle
                    </p>
                </div>
            </div>

            <!-- Commissions Table with Filter -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">Commission Ledger Records</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Itemized transaction fees and royalty credits</p>
                    </div>
                    <form action="{{ route('admin.franchises.overview', $franchise->id) }}" method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="tab" value="commissions">
                        <select name="comm_type" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs font-semibold outline-none focus:border-blue-600">
                            <option value="">All Types</option>
                            <option value="delivery" {{ request('comm_type') === 'delivery' ? 'selected' : '' }}>Delivery Commission</option>
                            <option value="pickup" {{ request('comm_type') === 'pickup' ? 'selected' : '' }}>Pickup Commission</option>
                            <option value="royalty" {{ request('comm_type') === 'royalty' ? 'selected' : '' }}>State Royalty</option>
                        </select>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">AWB NUMBER</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">COMMISSION TYPE</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">CALCULATED AMOUNT</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">STATUS</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">EARNED AT</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider text-right">SHIPMENT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                            @if($commissions && $commissions->isNotEmpty())
                                @foreach($commissions as $comm)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-3.5 font-mono font-bold text-gray-900">
                                            {{ $comm->shipment ? $comm->shipment->awb_number : ('Shipment #' . $comm->shipment_id) }}
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                                {{ $comm->commission_type === 'delivery' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($comm->commission_type === 'pickup' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-purple-50 text-purple-700 border border-purple-100') }}">
                                                {{ $comm->commission_type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5 font-bold text-gray-900 font-mono text-xs">
                                            ₹{{ number_format($comm->amount, 2) }}
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $comm->status === 'credited' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                                                {{ $comm->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5 text-gray-400">
                                            {{ $comm->created_at ? $comm->created_at->format('d M Y, h:i A') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-3.5 text-right">
                                            @if($comm->shipment)
                                                <a href="{{ route('admin.shipments.slip', $comm->shipment->awb_number) }}" target="_blank" class="px-2.5 py-1 rounded bg-gray-100 hover:bg-gray-200 font-semibold text-[11px] text-gray-700 inline-flex items-center gap-1 transition">
                                                    <i class="fa-solid fa-receipt text-[10px]"></i> Slip
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center py-10 text-gray-400">
                                        <i class="fa-solid fa-receipt text-2xl mb-1 text-gray-300"></i>
                                        <p>No commission records found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if($commissions && $commissions->hasPages())
                    <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50/50">
                        {{ $commissions->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- ==================== TAB 5: WALLET TRANSACTIONS ==================== -->
    @if($activeTab === 'wallet')
        <div class="space-y-6" x-data="{ showCreditModal: false, creditAmount: 500, creditRemarks: '' }">
            <!-- Wallet Balance Card Banner (Enterprise SaaS Style) -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">
                        Franchise Commission Wallet Balance
                    </div>
                    <div class="text-3xl font-black text-gray-900">
                        ₹{{ number_format($metrics->wallet_balance, 2) }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Account User: <span class="font-semibold text-gray-800">{{ $franchise->user->name ?? $franchise->owner_name }} ({{ $franchise->user->email ?? $franchise->email }})</span>
                    </div>
                </div>
                <div class="shrink-0 flex items-center gap-3">
                    <button type="button" @click="showCreditModal = true" class="px-3.5 py-2 rounded-lg text-xs font-bold bg-[#1e1b4b] text-white hover:bg-black shadow-sm transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-plus text-[10px]"></i> Credit Wallet
                    </button>
                    <span class="px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-xs font-semibold text-gray-600 inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-gray-400"></i> Earnings Segregated From COD
                    </span>
                </div>
            </div>

            <!-- Credit Wallet Modal -->
            <div x-show="showCreditModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-transition.opacity>
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showCreditModal = false">
                    <div class="px-6 py-4 bg-[#1e1b4b] text-white flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-wallet text-amber-400"></i>
                            <h3 class="font-bold text-sm">Credit Franchise Wallet</h3>
                        </div>
                        <button type="button" @click="showCreditModal = false" class="text-gray-300 hover:text-white">
                            <i class="fa-solid fa-xmark fa-lg"></i>
                        </button>
                    </div>

                    <form action="{{ route('admin.franchises.wallet.credit', $franchise->id) }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 leading-relaxed">
                            <strong>Note:</strong> Crediting this wallet will allow <strong>{{ $franchise->company_name }}</strong> to book shipments immediately. An audit log with your name will be permanently recorded.
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Credit Amount (₹) <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" x-model.number="creditAmount" min="1" step="0.01" required placeholder="500" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm font-bold text-gray-900 outline-none focus:border-[#1e1b4b]">
                            <div class="flex gap-2 mt-2">
                                <button type="button" @click="creditAmount = 500" class="px-2.5 py-1 text-[11px] font-bold bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">+ ₹500</button>
                                <button type="button" @click="creditAmount = 1000" class="px-2.5 py-1 text-[11px] font-bold bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">+ ₹1,000</button>
                                <button type="button" @click="creditAmount = 5000" class="px-2.5 py-1 text-[11px] font-bold bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">+ ₹5,000</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Remarks / Audit Note</label>
                            <input type="text" name="remarks" x-model="creditRemarks" placeholder="e.g. Promotional Credit / Deposit Topup" maxlength="255" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs outline-none focus:border-[#1e1b4b]">
                        </div>

                        <div class="flex justify-end items-center gap-3 pt-3 border-t border-gray-100">
                            <button type="button" @click="showCreditModal = false" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 bg-[#1e1b4b] hover:bg-black text-white rounded-xl text-xs font-bold shadow-sm transition inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-[10px]"></i> Confirm Credit
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Wallet Transactions Table -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 tracking-tight">Wallet Transaction Ledger</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Historical credit settlements and debits</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">TRANSACTION ID</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">TYPE</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">AMOUNT</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">BALANCE AFTER</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">DESCRIPTION / NARRATION</th>
                                <th class="px-6 py-3 font-extrabold text-[10px] text-gray-500 uppercase tracking-wider">DATE & TIME</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                            @if($transactions && $transactions->isNotEmpty())
                                @foreach($transactions as $txn)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-3.5 font-mono font-semibold text-gray-800">
                                            #{{ $txn->id }}
                                        </td>
                                        <td class="px-6 py-3.5">
                                            @if($txn->type === 'credit')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100 inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-arrow-down text-[9px]"></i> Credit
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-100 inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-arrow-up text-[9px]"></i> Debit
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3.5 font-mono font-bold text-xs {{ $txn->type === 'credit' ? 'text-emerald-700' : 'text-rose-600' }}">
                                            {{ $txn->type === 'credit' ? '+' : '-' }}₹{{ number_format($txn->amount, 2) }}
                                        </td>
                                        <td class="px-6 py-3.5 font-mono text-gray-700">
                                            ₹{{ number_format($txn->balance_after ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-3.5 max-w-xs truncate text-gray-700">
                                            {{ $txn->description ?? 'Commission / System entry' }}
                                        </td>
                                        <td class="px-6 py-3.5 text-gray-400">
                                            {{ $txn->created_at ? $txn->created_at->format('d M Y, h:i A') : 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center py-10 text-gray-400">
                                        <i class="fa-solid fa-wallet text-2xl mb-1 text-gray-300"></i>
                                        <p>No wallet transactions found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if($transactions && $transactions->hasPages())
                    <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50/50">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
