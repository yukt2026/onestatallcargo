@extends('layouts.admin')

@section('title', 'Serviceability Management - OneStall Cargo')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Serviceability & Pincodes</h1>
            <p class="text-sm text-gray-500 mt-1">Manage delivery coverage across OneStall Cargo hubs and external courier networks</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="document.getElementById('addPincodeModal').classList.remove('hidden')" class="px-4 py-2 bg-[var(--gold)] text-gray-900 font-bold rounded-xl shadow-sm hover:bg-yellow-500 transition text-sm flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Add Pincode
            </button>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Active Pincodes</p>
                <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalActive) }}</h3>
                <p class="text-xs text-emerald-600 font-semibold mt-1"><i class="fa-solid fa-circle-check mr-1"></i>Operational Coverage</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-xs">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">OneStall Hub Delivery</p>
                <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($onestallCovered) }}</h3>
                <p class="text-xs text-blue-600 font-semibold mt-1"><i class="fa-solid fa-warehouse mr-1"></i>Direct Franchise Fulfilled</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-xs">
                <i class="fa-solid fa-building-shield"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">External Provider Only</p>
                <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($externalOnly) }}</h3>
                <p class="text-xs text-amber-600 font-semibold mt-1"><i class="fa-solid fa-truck-fast mr-1"></i>3PL Courier Aggregated</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-xs">
                <i class="fa-solid fa-network-wired"></i>
            </div>
        </div>
    </div>

    <!-- Search / Filter -->
    <form action="{{ route('admin.serviceability.index') }}" method="GET" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-wrap gap-3 items-center">
        <div class="flex-1 min-w-[220px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Pincode, City, or State..." class="w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[var(--gold)]">
        </div>
        <button type="submit" class="px-4 py-2 bg-[#1e1b4b] hover:bg-black text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-magnifying-glass"></i> Search
        </button>
        @if(request('search'))
            <a href="{{ route('admin.serviceability.index') }}" class="px-3 py-2 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold hover:bg-rose-100 transition">
                Reset
            </a>
        @endif
    </form>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                        <th class="px-6 py-4">Pincode</th>
                        <th class="px-6 py-4">City</th>
                        <th class="px-6 py-4">State</th>
                        <th class="px-6 py-4">Assigned Franchise Hub</th>
                        <th class="px-6 py-4">Fulfillment Mode</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-gray-700 font-medium text-xs">
                    @forelse($pincodes as $pin)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-black text-gray-900 tracking-wide">
                                {{ $pin->pincode }}
                            </td>
                            <td class="px-6 py-4 text-gray-800">{{ $pin->city ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $pin->state ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if($pin->franchise)
                                    <span class="font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200">
                                        <i class="fa-solid fa-warehouse mr-1 text-[10px]"></i>{{ $pin->franchise->company_name }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic">No Hub Assigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($pin->franchise_id)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                        OneStall Direct
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                                        Aggregated 3PL
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($pin->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-xs font-semibold">
                                <i class="fa-solid fa-location-dot text-2xl mb-2 text-gray-300"></i><br>
                                No serviceable pincodes found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pincodes->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $pincodes->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Add Pincode -->
<div id="addPincodeModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-70 flex items-center justify-center p-4">
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
        <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
            <h3 class="text-base font-extrabold text-gray-900">Add Serviceable Pincode</h3>
            <button type="button" onclick="document.getElementById('addPincodeModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="{{ route('admin.serviceability.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Pincode <span class="text-red-500">*</span></label>
                <input type="text" name="pincode" required maxlength="6" pattern="[0-9]{6}" placeholder="e.g. 110001" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[var(--gold)]">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">City</label>
                    <input type="text" name="city" placeholder="e.g. New Delhi" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[var(--gold)]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">State</label>
                    <input type="text" name="state" placeholder="e.g. Delhi" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[var(--gold)]">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Assign Franchise Hub (Optional)</label>
                <select name="franchise_id" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[var(--gold)] bg-white">
                    <option value="">No Hub (External 3PL Only)</option>
                    @foreach($franchises as $fh)
                        <option value="{{ $fh->id }}">{{ $fh->company_name }} ({{ $fh->city }}, {{ $fh->district }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('addPincodeModal').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-gray-900 bg-[var(--gold)] hover:bg-yellow-500 rounded-xl transition shadow-sm">
                    Save Pincode
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
