@extends('layouts.hub')

@section('content')
<div class="space-y-6" x-data="{ 
    selectedShipments: [],
    selectAll: false,
    toggleAll() {
        if (this.selectAll) {
            this.selectedShipments = Array.from(document.querySelectorAll('.shipment-checkbox')).map(cb => cb.value);
        } else {
            this.selectedShipments = [];
        }
    }
}">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-extrabold text-gray-900">Pickup Assignments</h2>
    </div>

    <!-- Stats or Filters can go here in the future -->
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Pending Pickups</h3>
            <form action="{{ route('hub.assignments.assign') }}" method="POST" class="flex gap-3 items-center" x-show="selectedShipments.length > 0">
                @csrf
                <input type="hidden" name="type" value="pickup">
                <template x-for="id in selectedShipments" :key="id">
                    <input type="hidden" name="shipment_ids[]" :value="id">
                </template>
                <select name="rider_id" required class="text-sm px-3 py-1.5 border border-gray-300 rounded-lg outline-none focus:border-[var(--gold)]">
                    <option value="">-- Assign Rider --</option>
                    @foreach($riders as $rider)
                        <option value="{{ $rider->id }}">{{ $rider->user->name }} ({{ $rider->vehicle_type ?? 'No Vehicle' }})</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-1.5 bg-[var(--gold)] text-gray-900 font-bold rounded-lg hover:bg-yellow-500 transition text-sm">
                    Assign <span x-text="selectedShipments.length"></span> Shipments
                </button>
            </form>
        </div>

        <!-- Pickups Filters Bar -->
        <form action="{{ route('hub.assignments.pickups') }}" method="GET" class="p-3 bg-[#f8fafc] border-b border-gray-100 flex flex-wrap items-center justify-between gap-2.5">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Quick Date Preset -->
                <select name="quick_date" onchange="if(this.value && this.value !== 'custom'){ document.getElementById('pickupStartDate').value=''; document.getElementById('pickupEndDate').value=''; } this.form.submit()" class="bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-gray-700 outline-none">
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
                    <input type="date" name="start_date" id="pickupStartDate" value="{{ request('start_date') }}" class="font-semibold text-gray-700 outline-none text-xs bg-transparent">
                    <span class="text-gray-300">|</span>
                    <span class="text-gray-400 font-bold text-[10px] uppercase">To</span>
                    <input type="date" name="end_date" id="pickupEndDate" value="{{ request('end_date') }}" class="font-semibold text-gray-700 outline-none text-xs bg-transparent">
                </div>

                <!-- Search Input -->
                <div class="relative flex items-center">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search AWB / Customer..." class="bg-white border border-gray-200 rounded-xl pl-2.5 pr-7 py-1.5 text-xs font-semibold text-gray-700 outline-none w-44 sm:w-52 focus:border-[#4338ca]">
                    <button type="submit" class="absolute right-2 text-gray-400 hover:text-[#4338ca]"><i class="fa-solid fa-magnifying-glass text-[10px]"></i></button>
                </div>

                <button type="submit" class="px-3 py-1.5 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-filter text-[10px]"></i> Filter
                </button>
            </div>

            @if(request('start_date') || request('end_date') || request('quick_date') || request('search'))
            <a href="{{ route('hub.assignments.pickups') }}" class="px-3 py-1.5 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-700 flex items-center gap-1.5 hover:bg-rose-100 shadow-xs">
                <i class="fa-solid fa-rotate-right text-rose-500"></i> Reset
            </a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-[10px] font-extrabold uppercase text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-center w-10">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll" class="rounded border-gray-300 text-[var(--gold)] focus:ring-[var(--gold)]">
                        </th>
                        <th class="px-4 py-3">AWB / Order ID</th>
                        <th class="px-4 py-3">Pickup Location</th>
                        <th class="px-4 py-3">Destination</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Assigned Rider</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium">
                    @forelse($shipments as $shipment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox" value="{{ $shipment->id }}" x-model="selectedShipments" class="shipment-checkbox rounded border-gray-300 text-[var(--gold)] focus:ring-[var(--gold)]">
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-900">{{ $shipment->awb_number }}</div>
                            <div class="text-xs text-gray-500">{{ $shipment->order_id }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-900 truncate max-w-[180px]">{{ !empty(trim($shipment->pickup_name ?? '')) ? $shipment->pickup_name : (optional($shipment->user)->company_name ?: optional($shipment->user)->name ?: 'Warehouse') }}</div>
                            @if(!empty($shipment->pickup_address))
                                <div class="text-[11px] text-gray-500 line-clamp-1 max-w-[180px]" title="{{ $shipment->pickup_address }}">{{ $shipment->pickup_address }}</div>
                            @endif
                            <div class="text-xs text-gray-600 font-medium">{{ $shipment->pickup_city }} <span class="text-gray-400">({{ $shipment->pickup_pincode }})</span></div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $shipment->delivery_city }}</div>
                            <div class="text-xs text-gray-500">PIN: {{ $shipment->delivery_pincode }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-md text-[10px] font-bold tracking-wider bg-blue-50 text-blue-700">
                                {{ $shipment->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($shipment->rider_id && ($rUser = $shipment->assignedRider))
                                <div class="font-bold text-gray-900">{{ $rUser->name }}</div>
                                @if($rUser->rider)
                                    <div class="flex gap-2 mt-0.5">
                                        <a href="{{ route('hub.fleet.report', $rUser->rider->id) }}" class="text-[10px] text-indigo-600 font-bold hover:underline"><i class="fa-solid fa-chart-pie mr-0.5"></i>Report</a>
                                        <a href="{{ route('hub.fleet.runsheet', $rUser->rider->id) }}" target="_blank" class="text-[10px] text-emerald-700 font-bold hover:underline"><i class="fa-solid fa-print mr-0.5"></i>DRS</a>
                                    </div>
                                @endif
                            @else
                                <span class="text-gray-400 font-medium">Unassigned</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No pending pickups available for assignment.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-3 border-t border-gray-100">{{ $shipments->links() }}</div>
        </div>
    </div>
</div>
@endsection
