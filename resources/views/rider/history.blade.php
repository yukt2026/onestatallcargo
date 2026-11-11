@extends('layouts.rider')
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-black text-gray-900">Task History</h1>
        <a href="{{ route('rider.profile') }}" class="text-xs font-bold text-gray-500 hover:text-gray-900">&larr; Back</a>
    </div>

    <div class="space-y-4">
        @forelse($history as $shipment)
        @php
            // Determine if this was a Pickup or Delivery task based on its final rider state
            $isPickupTask = $shipment->status === 'In Transit';
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full {{ $isPickupTask ? 'bg-purple-500' : 'bg-blue-500' }}"></div>
            
            <div class="flex justify-between items-start mb-3">
                <div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $shipment->updated_at->format('d M, h:i A') }}</div>
                    <div class="text-sm font-black text-gray-900 mt-0.5">{{ $shipment->awb_number }}</div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider mb-1
                        {{ $shipment->status == 'Delivered' ? 'bg-green-100 text-green-700' : 
                          ($shipment->status == 'In Transit' ? 'bg-purple-100 text-purple-700' : 'bg-red-100 text-red-700') }}">
                        {{ $shipment->status }}
                    </span>
                    <span class="text-[9px] font-bold text-gray-500 uppercase">{{ $isPickupTask ? 'Pickup Task' : 'Delivery Task' }}</span>
                </div>
            </div>
            
            @if($isPickupTask)
                <!-- Pickup History Details -->
                <div class="bg-gray-50 rounded-xl p-3 mb-3 border border-gray-100">
                    <div class="text-xs text-gray-700 font-bold mb-1"><i class="fa-solid fa-location-dot text-purple-500 w-4"></i> Picked Up From:</div>
                    <div class="text-sm font-extrabold text-gray-900 ml-5 mb-1">{{ !empty(trim($shipment->pickup_name ?? '')) ? $shipment->pickup_name : ($shipment->user->company_name ?? $shipment->user->name ?? 'Seller') }}</div>
                    <div class="text-xs text-gray-600 ml-5 leading-tight">{{ !empty(trim($shipment->pickup_address ?? '')) ? $shipment->pickup_address : 'Warehouse' }}, {{ $shipment->pickup_city ?? '' }} - <span class="font-bold text-gray-800">{{ $shipment->pickup_pincode ?? '' }}</span></div>
                    <div class="text-xs font-bold text-purple-600 mt-2 ml-5"><i class="fa-solid fa-phone w-4"></i> <a href="tel:{{ $shipment->pickup_phone ?? $shipment->user->phone ?? '' }}">{{ $shipment->pickup_phone ?? $shipment->user->phone ?? 'N/A' }}</a></div>
                </div>
            @else
                <!-- Delivery History Details -->
                <div class="bg-gray-50 rounded-xl p-3 mb-3 border border-gray-100">
                    <div class="text-xs text-gray-700 font-bold mb-1"><i class="fa-solid fa-location-dot text-blue-500 w-4"></i> Delivered To:</div>
                    <div class="text-sm font-extrabold text-gray-900 ml-5 mb-1">{{ $shipment->receiver_name }}</div>
                    <div class="text-xs text-gray-600 ml-5 leading-tight">{{ $shipment->delivery_address }}, {{ $shipment->delivery_city }} - <span class="font-bold text-gray-800">{{ $shipment->delivery_pincode }}</span></div>
                    <div class="text-xs font-bold text-blue-600 mt-2 ml-5"><i class="fa-solid fa-phone w-4"></i> <a href="tel:{{ $shipment->receiver_phone }}">{{ $shipment->receiver_phone }}</a></div>
                </div>
                
                @if($shipment->status === 'NDR')
                <div class="mb-3 bg-red-50 border border-red-100 text-red-800 text-xs font-bold p-3 rounded-xl">
                    <div class="uppercase text-[9px] tracking-wider mb-0.5 text-red-600">NDR Reason</div>
                    {{ $shipment->ndr_reason ?? 'Customer Unavailable' }}
                </div>
                @endif
            @endif

            <div class="grid grid-cols-3 gap-2">
                <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                    <div class="text-[9px] text-gray-400 font-bold uppercase">Weight & Dims</div>
                    <div class="text-xs font-bold text-gray-800">{{ $shipment->weight_kg }} kg</div>
                    <div class="text-[9px] text-gray-500">{{ $shipment->length_cm }}x{{ $shipment->width_cm }}x{{ $shipment->height_cm }} cm</div>
                </div>
                <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                    <div class="text-[9px] text-gray-400 font-bold uppercase">Items</div>
                    <div class="text-xs font-bold text-gray-800">{{ $shipment->product_qty ? $shipment->product_qty . ' Pcs' : '1 Box' }}</div>
                </div>
                <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                    <div class="text-[9px] text-gray-400 font-bold uppercase">Payment Type</div>
                    <div class="text-xs font-bold {{ $shipment->is_cod ? 'text-yellow-600' : 'text-green-600' }}">{{ $shipment->is_cod ? 'COD' : 'Prepaid' }}</div>
                    @if($shipment->is_cod && $shipment->status === 'Delivered')
                        <div class="text-[9px] font-black text-gray-900 mt-0.5">?{{ $shipment->invoice_value }}</div>
                    @endif
                </div>
            </div>
            
            @if($shipment->video_evidence_url)
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <a href="{{ $shipment->video_evidence_url }}" target="_blank" class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider flex items-center justify-center gap-1 bg-indigo-50 py-2 rounded-lg">
                        <i class="fa-solid fa-play-circle"></i> View Uploaded Evidence
                    </a>
                </div>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8 text-center text-gray-400">
            <i class="fa-solid fa-clock-rotate-left text-4xl mb-2 text-gray-300"></i>
            <p class="font-bold text-sm">No task history found.</p>
        </div>
        @endforelse
        
        @if($history->hasPages())
        <div class="p-4 bg-white rounded-2xl shadow-sm border border-gray-200">{{ $history->links() }}</div>
        @endif
    </div>
</div>
@endsection