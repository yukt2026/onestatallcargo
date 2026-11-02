@extends('layouts.admin')
@section('title', 'Shipment Invoices - OneStall Cargo')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Shipment Commercial Invoices</h1>
            <p class="text-sm text-gray-500 mt-1">Review, print, and audit invoices generated across all shipments.</p>
        </div>
        <a href="{{ route('admin.billing.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back to COD Ledgers
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
        <form method="GET" action="{{ route('admin.billing.invoices') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search AWB, Order ID, Receiver..." class="w-full text-xs px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <select name="payment_mode" class="w-full text-xs px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Payment Modes</option>
                    <option value="prepaid" {{ request('payment_mode') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                    <option value="cod" {{ request('payment_mode') === 'cod' ? 'selected' : '' }}>Cash on Delivery (COD)</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-900 hover:bg-black text-white text-xs font-bold rounded-lg transition">Filter</button>
                <a href="{{ route('admin.billing.invoices') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                        <th class="px-6 py-4">AWB & Order ID</th>
                        <th class="px-6 py-4">Seller / Shipper</th>
                        <th class="px-6 py-4">Consignee</th>
                        <th class="px-6 py-4">Invoice Value</th>
                        <th class="px-6 py-4">Payment</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-black text-indigo-950">{{ $inv->awb_number }}</div>
                            <div class="text-[10px] text-gray-400">Ord: {{ $inv->order_id ?: 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $inv->pickup_name ?: ($inv->user->company_name ?? $inv->user->name ?? 'N/A') }}</div>
                            <div class="text-[10px] text-gray-400">{{ $inv->pickup_city ?: 'Origin' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800">{{ $inv->receiver_name }}</div>
                            <div class="text-[10px] text-gray-400">{{ $inv->delivery_city }}, {{ $inv->delivery_pincode }}</div>
                        </td>
                        <td class="px-6 py-4 font-black text-gray-900">
                            &#8377; {{ number_format($inv->invoice_value ?: $inv->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            @if($inv->is_cod)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">COD</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">Prepaid</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                            <a href="{{ route('admin.shipments.invoice', $inv->awb_number) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-900 font-bold rounded-lg transition inline-flex items-center gap-1">
                                <i class="fa-solid fa-file-invoice"></i> View Invoice
                            </a>
                            <a href="{{ route('admin.shipments.slip', $inv->awb_number) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition inline-flex items-center gap-1">
                                <i class="fa-solid fa-tag"></i> Slip
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            No shipment invoices found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
