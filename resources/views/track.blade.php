@extends('layouts.public')
@section('title', 'Track Shipment | OneStall Cargo')

@section('content')
<!-- Minimalist Hero & Search Section -->
<section class="bg-gradient-to-r from-blue-50/40 via-white to-blue-50/40 py-10 md:py-12 relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="inline-block px-3 py-0.5 rounded-full border border-blue-200 bg-blue-50 text-brand-navy text-[10px] font-bold mb-3 uppercase tracking-wider">
            Live Order Tracking
        </div>
        <h1 class="text-2xl md:text-4xl font-extrabold text-brand-navy leading-tight mb-2">
            Track Your <span class="text-brand-red">Shipment</span>
        </h1>
        <p class="text-xs md:text-sm text-gray-600 mb-6 font-medium max-w-md mx-auto">
            Enter your AWB tracking number or Shipment ID below for real-time delivery status.
        </p>
        
        @if(session('error'))
            <div class="mb-4 text-xs font-bold text-red-600 bg-red-50 py-2 px-4 rounded-full inline-block border border-red-200">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ session('error') }}
            </div>
        @endif
        
        <!-- Search Form -->
        <form action="{{ route('track.post') }}" method="POST" class="max-w-xl mx-auto mb-4">
            @csrf
            <div class="relative flex items-center bg-white rounded-full border border-gray-200 shadow-md p-1.5 focus-within:border-brand-navy transition">
                <i class="fa-solid fa-magnifying-glass text-gray-400 pl-4 pr-2 text-sm"></i>
                <input type="text" name="awb_number" value="{{ request('awb_number') ?? (isset($shipment) ? $shipment->awb_number : '') }}" placeholder="Enter AWB Number (e.g. OSC12345678)..." class="w-full text-xs md:text-sm font-semibold text-gray-900 bg-transparent focus:outline-none placeholder-gray-400" required>
                <button type="submit" class="px-6 py-2.5 rounded-full bg-brand-red text-white font-bold text-xs hover:bg-brand-redHover transition shadow-sm whitespace-nowrap">
                    Track Order
                </button>
            </div>
        </form>
        <div class="text-[11px] text-gray-400 font-medium">Supports Delhivery, BlueDart, XpressBees, Shadowfax & all 15+ carriers</div>
    </div>
</section>

<!-- Tracking Results Area -->
<section class="py-8 bg-gray-50/60 min-h-[50vh]">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        @if(isset($shipment))
            <div class="bg-white rounded-2xl border border-gray-100 shadow-md p-6 md:p-8 space-y-6">
                <!-- Header Info -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 border-b border-gray-100 gap-4">
                    <div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">AWB Number</div>
                        <h2 class="text-xl md:text-2xl font-black text-brand-navy font-mono">{{ $shipment->awb_number }}</h2>
                    </div>
                    @php
                        $s = strtolower(trim($shipment->status ?? ''));
                        if ($s === 'delivered') {
                            $pillClass = 'bg-emerald-100 text-emerald-800 border border-emerald-300';
                        } elseif (str_starts_with($s, 'rto')) {
                            $pillClass = 'bg-orange-100 text-orange-800 border border-orange-300';
                        } elseif (str_starts_with($s, 'cancel') || in_array($s, ['ndr', 'action_required', 'undelivered'])) {
                            $pillClass = 'bg-rose-100 text-rose-800 border border-rose-300';
                        } elseif (in_array($s, ['out for delivery', 'out_for_delivery', 'ofd'])) {
                            $pillClass = 'bg-cyan-100 text-cyan-800 border border-cyan-300';
                        } elseif (in_array($s, ['in transit', 'in_transit', 'transit'])) {
                            $pillClass = 'bg-purple-100 text-purple-800 border border-purple-300';
                        } else {
                            $pillClass = 'bg-blue-100 text-blue-800 border border-blue-300';
                        }
                    @endphp
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-blue-50 border border-blue-200 text-brand-navy font-bold rounded-full text-xs uppercase">
                            {{ $shipment->courier_partner ?? $shipment->courier_name ?? 'OneStall Express' }}
                        </span>
                        <span class="px-3 py-1 {{ $pillClass }} font-extrabold uppercase rounded-full text-xs">
                            {{ $shipment->status }}
                        </span>
                    </div>
                </div>

                @php
                    $progress = 0;
                    $step1 = false; $step2 = false; $step3 = false; $step4 = false;
                    $isException = false;

                    if (in_array($s, ['new', 'booked', 'manifested', 'pickup scheduled', 'pickup_scheduled', 'pickup pending', 'pickup_pending']) || str_starts_with($s, 'pickup')) {
                        $progress = 25;
                        $step1 = true;
                    } elseif (in_array($s, ['picked_up', 'pickups', 'dispatched', 'bagged', 'sealed', 'received'])) {
                        $progress = 50;
                        $step1 = true; $step2 = true;
                    } elseif (in_array($s, ['in_transit', 'in transit', 'transit', 'out for delivery', 'out_for_delivery', 'ofd'])) {
                        $progress = 75;
                        $step1 = true; $step2 = true; $step3 = true;
                    } elseif (in_array($s, ['delivered'])) {
                        $progress = 100;
                        $step1 = true; $step2 = true; $step3 = true; $step4 = true;
                    } elseif (str_starts_with($s, 'rto') || in_array($s, ['ndr', 'action_required', 'undelivered']) || str_starts_with($s, 'cancel')) {
                        $progress = 75;
                        $step1 = true; $step2 = true; $step3 = true;
                        $isException = true;
                    } else {
                        $progress = 25;
                        $step1 = true;
                    }
                @endphp

                <!-- Progress Stepper Bar -->
                <div class="py-4">
                    <div class="relative flex items-center justify-between">
                        <!-- Line behind -->
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-100 z-0"></div>
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 {{ $isException ? 'bg-red-500' : 'bg-brand-red' }} z-0 transition-all duration-1000" style="width: {{ $progress }}%;"></div>

                        <!-- Step 1 -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-7 h-7 rounded-full {{ $step1 ? 'bg-brand-red text-white shadow' : 'bg-gray-200 text-gray-400' }} flex items-center justify-center text-xs font-bold">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <span class="text-[10px] font-bold {{ $step1 ? 'text-gray-800' : 'text-gray-400' }} mt-2">Booked</span>
                        </div>

                        <!-- Step 2 -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-7 h-7 rounded-full {{ $step2 ? 'bg-brand-red text-white shadow' : 'bg-gray-200 text-gray-400' }} flex items-center justify-center text-xs font-bold">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <span class="text-[10px] font-bold {{ $step2 ? 'text-gray-800' : 'text-gray-400' }} mt-2">Dispatched</span>
                        </div>

                        <!-- Step 3 -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-7 h-7 rounded-full {{ $step3 ? 'bg-brand-navy text-white shadow' : 'bg-gray-200 text-gray-400' }} flex items-center justify-center text-xs font-bold {{ $step3 && !$step4 && !$isException ? 'ring-4 ring-blue-100' : '' }}">
                                <i class="fa-solid fa-truck-fast text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-bold {{ $step3 ? 'text-brand-navy' : 'text-gray-400' }} mt-2">In Transit</span>
                        </div>

                        <!-- Step 4 -->
                        <div class="relative z-10 flex flex-col items-center">
                            @if($isException)
                                <div class="w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-xs font-bold shadow ring-4 ring-red-100">
                                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-bold text-red-500 mt-2">Exception</span>
                            @else
                                <div class="w-7 h-7 rounded-full {{ $step4 ? 'bg-green-500 text-white shadow' : 'bg-gray-200 text-gray-400' }} flex items-center justify-center text-xs font-bold">
                                    <i class="fa-solid fa-house text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-bold {{ $step4 ? 'text-green-600' : 'text-gray-400' }} mt-2">Delivered</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Shipment Details Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 bg-gray-50/80 rounded-xl border border-gray-100 text-xs">
                    <div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase">Origin</div>
                        <div class="font-bold text-gray-900">{{ $shipment->pickup_city ?? 'Seller City' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase">Destination</div>
                        <div class="font-bold text-gray-900">{{ $shipment->delivery_city ?? 'Destination' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase">Payment Mode</div>
                        <div class="font-bold {{ $shipment->is_cod ? 'text-brand-red' : 'text-green-600' }}">{{ $shipment->is_cod ? 'COD (₹'.$shipment->total_amount.')' : 'Prepaid' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase">Last Updated</div>
                        <div class="font-bold text-gray-900">{{ $shipment->updated_at ? $shipment->updated_at->diffForHumans() : 'Just now' }}</div>
                    </div>
                </div>

                <!-- Timeline Audit Trail -->
                <div class="pt-2">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Milestone Timeline</h3>
                    <div class="relative pl-6 border-l-2 {{ $isException ? 'border-red-500' : 'border-brand-red' }} space-y-6">
                        
                        @if($shipment->events && $shipment->events->count() > 0)
                            @foreach($shipment->events as $index => $event)
                                @php
                                    $isFirst = $index === 0;
                                    $isErr = in_array(strtolower($event->status), ['ndr', 'rto', 'cancelled', 'rto initiated', 'rto delivered']);
                                @endphp
                                <div class="relative">
                                    @if($isFirst)
                                        <div class="absolute -left-[31px] top-0 w-4 h-4 {{ $isErr ? 'bg-red-500' : 'bg-brand-navy' }} rounded-full border-2 border-white shadow"></div>
                                    @else
                                        <div class="absolute -left-[31px] top-0 w-3.5 h-3.5 {{ $isErr ? 'bg-red-300' : 'bg-gray-300' }} rounded-full border-2 border-white"></div>
                                    @endif
                                    <p class="text-[10px] font-bold text-gray-400">{{ $event->created_at->format('d M Y, h:i A') }}</p>
                                    <p class="text-sm font-bold {{ $isErr ? 'text-red-500' : 'text-gray-900' }}">{{ $event->status }} @if($event->location) - {{ $event->location }} @endif</p>
                                    <p class="text-xs text-gray-600">{{ $event->remarks ?? 'Status updated to ' . strtolower($event->status) }}</p>
                                </div>
                            @endforeach
                        @else
                            <div class="relative">
                                <div class="absolute -left-[31px] top-0 w-4 h-4 {{ $isException ? 'bg-red-500' : 'bg-brand-navy' }} rounded-full border-2 border-white shadow"></div>
                                <p class="text-[10px] font-bold text-gray-400">{{ $shipment->updated_at ? $shipment->updated_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</p>
                                <p class="text-sm font-bold {{ $isException ? 'text-red-500' : 'text-gray-900' }}">Current Status: {{ $shipment->status }}</p>
                                <p class="text-xs text-gray-600">The package is currently marked as {{ strtolower($shipment->status) }}.</p>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        @elseif(request()->isMethod('post'))
            <div class="bg-white rounded-2xl border border-gray-100 shadow-md p-8 text-center max-w-md mx-auto">
                <div class="w-12 h-12 rounded-full bg-red-50 text-brand-red flex items-center justify-center mx-auto mb-3 text-xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Shipment Not Found</h3>
                <p class="text-xs text-gray-500 mb-6">We couldn't find a shipment with that AWB number. Please double check the AWB and try again.</p>
                <a href="{{ route('track') }}" class="px-5 py-2 rounded-full bg-brand-navy text-white text-xs font-bold hover:bg-brand-blue transition inline-block">
                    Try Another Search
                </a>
            </div>
        @else
            <!-- Minimalist Default Guidance Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center max-w-md mx-auto">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-brand-navy flex items-center justify-center mx-auto mb-3 text-lg">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Track Direct From Your Store</h3>
                <p class="text-xs text-gray-500 leading-relaxed">
                    You can find your AWB tracking number in the order confirmation email or SMS sent by your seller.
                </p>
            </div>
        @endif

    </div>
</section>

<!-- Bottom Support Strip -->
<section class="py-6 bg-white border-t border-gray-100 text-center text-xs text-gray-500">
    <div class="max-w-xl mx-auto px-4">
        Need assistance with your package delivery? <a href="{{ route('help') }}" class="font-bold text-brand-red hover:underline">Contact Support Center</a>
    </div>
</section>
@endsection