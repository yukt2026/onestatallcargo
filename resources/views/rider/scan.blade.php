@extends('layouts.rider')
@section('title', 'Scan / Search Shipment')
@section('content')
<div class="space-y-6">
    <!-- Scanner UI -->
    <div class="bg-black text-white p-6 rounded-3xl text-center relative overflow-hidden h-40 flex flex-col justify-center shadow-lg border border-gray-800">
        <i class="fa-solid fa-qrcode text-4xl text-gray-700 animate-pulse mb-3"></i>
        <h2 class="text-lg font-bold">Camera Scanner Active</h2>
        <p class="text-xs text-gray-400 mt-1">Point at AWB Barcode</p>
        <div class="absolute inset-0 border-4 border-[#FFD700] m-6 rounded-xl opacity-50"></div>
        <div class="absolute w-full h-0.5 bg-green-500 opacity-70 top-1/2 left-0" style="animation: scan 2s infinite alternate;"></div>
    </div>
    
    <!-- Manual Entry -->
    <div class="bg-white p-4 rounded-3xl shadow-sm border border-gray-200">
        <h3 class="font-bold text-gray-900 mb-2">Manual Entry Fallback</h3>
        <p class="text-[10px] text-gray-500 mb-3">If barcode is unreadable, type the AWB number manually.</p>
        <form action="{{ route('rider.scan') }}" method="GET" class="flex gap-2">
            <input type="text" name="awb" value="{{ $awb ?? '' }}" placeholder="Enter AWB Number" class="flex-1 bg-gray-50 border border-gray-200 px-4 py-3 rounded-xl outline-none text-sm font-bold uppercase focus:border-[#4338ca]" required>
            <button type="submit" class="bg-[#1e293b] text-white px-5 rounded-xl font-bold hover:bg-black transition"><i class="fa-solid fa-search"></i></button>
        </form>
    </div>

    @if($awb)
        @if($shipment)
            <!-- Comprehensive Details Card -->
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- Header -->
                <div class="bg-gray-50 border-b border-gray-200 p-4 flex justify-between items-center">
                    <div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">AWB Number</div>
                        <h3 class="font-black text-xl text-[#4338ca]">{{ $shipment->awb_number }}</h3>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ in_array($shipment->status, ['Delivered']) ? 'bg-green-100 text-green-800' : (in_array($shipment->status, ['Pending', 'Pickup Scheduled']) ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                        {{ $shipment->status }}
                    </span>
                </div>
                
                <!-- Shipment Vital Info -->
                <div class="p-4 grid grid-cols-2 gap-4 border-b border-gray-100">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <div class="text-[10px] text-gray-500 font-bold uppercase mb-1">Payment Type</div>
                        <div class="font-bold text-gray-900 flex items-center gap-1.5">
                            @if($shipment->is_cod)
                                <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded uppercase text-[10px]">COD</span>
                                &#8377; {{ number_format($shipment->invoice_value, 2) }}
                            @else
                                <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded uppercase text-[10px]">Prepaid</span>
                            @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <div class="text-[10px] text-gray-500 font-bold uppercase mb-1">Weight & Dims</div>
                        <div class="font-bold text-gray-900 text-xs">
                            {{ $shipment->physical_weight }} kg <br>
                            <span class="text-[10px] text-gray-500 font-medium">{{ $shipment->length }}x{{ $shipment->breadth }}x{{ $shipment->height }} cm</span>
                        </div>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="p-4 space-y-4 border-b border-gray-100">
                    <!-- Pickup (Seller) -->
                    <div class="relative pl-6">
                        <div class="absolute left-1 top-1 w-2.5 h-2.5 rounded-full bg-blue-500 ring-4 ring-blue-100"></div>
                        <div class="absolute left-2 top-4 bottom-0 w-0.5 bg-gray-200" style="height: calc(100% + 1rem);"></div>
                        <h4 class="text-xs font-extrabold text-gray-900 uppercase tracking-wider mb-1">Pickup From</h4>
                        <div class="text-sm font-bold text-gray-800">{{ !empty(trim($shipment->pickup_name ?? '')) ? $shipment->pickup_name : ($shipment->user->company_name ?? $shipment->user->name ?? 'Seller') }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ !empty(trim($shipment->pickup_address ?? '')) ? $shipment->pickup_address : 'Registered Warehouse' }}, {{ $shipment->pickup_city ?? '' }}{{ !empty($shipment->pickup_state) ? ', ' . $shipment->pickup_state : '' }} - {{ $shipment->pickup_pincode ?? '' }}</div>
                        <div class="mt-1 text-xs font-semibold text-blue-600"><i class="fa-solid fa-phone w-4"></i> {{ $shipment->pickup_phone ?? $shipment->user->phone ?? 'N/A' }}</div>
                    </div>
                    
                    <!-- Delivery (Customer) -->
                    <div class="relative pl-6 pt-2">
                        <div class="absolute left-1 top-3 w-2.5 h-2.5 rounded-full bg-green-500 ring-4 ring-green-100"></div>
                        <h4 class="text-xs font-extrabold text-gray-900 uppercase tracking-wider mb-1">Deliver To</h4>
                        <div class="text-sm font-bold text-gray-800">{{ $shipment->receiver_name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $shipment->delivery_address }}, {{ $shipment->delivery_city }}, {{ $shipment->delivery_state }} - {{ $shipment->delivery_pincode }}</div>
                        <div class="mt-1 text-xs font-semibold text-green-600"><i class="fa-solid fa-phone w-4"></i> {{ $shipment->receiver_phone }}</div>
                    </div>
                </div>

                <!-- Action Forms -->
                @if(in_array($shipment->status, ['Pending', 'Manifested', 'Pickup Scheduled']))
                    <!-- PICKUP ACTION -->
                    <div class="p-4 bg-blue-50">
                        <form action="{{ route('rider.evidence.upload') }}" method="POST">
                            @csrf
                            <input type="hidden" name="awb_number" value="{{ $shipment->awb_number }}">
                            <input type="hidden" name="action_type" value="Pickup">
                            <button type="submit" class="w-full py-3 bg-[#4338ca] hover:bg-indigo-700 text-white font-extrabold rounded-xl transition shadow-md">
                                <i class="fa-solid fa-box-open mr-1"></i> Confirm Successful Pickup
                            </button>
                        </form>
                    </div>
                @elseif(in_array($shipment->status, ['In Transit', 'Out for Delivery']))
                    <!-- DELIVERY ACTION -->
                    <div class="p-4 bg-yellow-50">
                        <form action="{{ route('rider.evidence.upload') }}" method="POST">
                            @csrf
                            <input type="hidden" name="awb_number" value="{{ $shipment->awb_number }}">
                            <input type="hidden" name="action_type" value="Delivery">
                            
                                                        @if($shipment->is_cod)
                                <div class="mb-4 p-4 border border-yellow-200 rounded-xl bg-white shadow-sm" x-data="scanPayment()">
                                    <div class="flex items-center justify-between mb-3 border-b border-gray-100 pb-2">
                                        <label class="block text-xs font-extrabold text-gray-900 uppercase tracking-wider">Payment Collection (COD)</label>
                                        <span class="font-black text-lg text-red-600">&#8377; {{ number_format($shipment->cod_amount > 0 ? $shipment->cod_amount : $shipment->invoice_value, 2) }}</span>
                                    </div>
                                    <select name="payment_mode" required x-model="payMode" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm font-bold focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 bg-gray-50">
                                        <option value="">-- Select Payment Mode --</option>
                                        <option value="Cash">Cash Collected</option>
                                        <option value="UPI">UPI / QR Code Scan</option>
                                    </select>
                                    
                                                                                                            <!-- Dynamic QR Code -->
                                    <div x-show="payMode === 'UPI'" x-init="$watch('payMode', val => { if(val === 'UPI' && !qrUrl) generateQr() })" class="mt-3 text-center p-3 bg-gray-50 border border-gray-200 rounded-xl" style="display:none;">
                                        <p class="text-xs font-bold text-gray-600 mb-2" x-show="!isVerified">Ask Customer to Scan Cashfree QR</p>
                                        <div class="bg-white p-2 inline-block rounded-xl shadow-sm border border-gray-100 min-h-[150px]" :class="isVerified ? 'border-green-500 bg-green-50' : ''">
                                            
                                            <!-- Success State -->
                                            <div x-show="isVerified" style="display:none;" class="w-32 h-32 flex flex-col items-center justify-center text-green-600">
                                                <i class="fa-solid fa-circle-check text-4xl mb-2"></i>
                                                <span class="text-xs font-extrabold">PAID</span>
                                            </div>

                                            <!-- QR State -->
                                            <img x-show="qrUrl && !isVerified" :src="qrUrl" alt="Cashfree QR Code" class="w-32 h-32 mx-auto" style="display:none;">
                                            <div x-show="!qrUrl && !isVerified" class="w-32 h-32 mx-auto flex flex-col items-center justify-center text-[10px] text-gray-500">
                                                <i class="fa-solid fa-spinner fa-spin text-xl mb-2 text-indigo-500"></i>
                                                Generating...
                                            </div>
                                        </div>
                                        
                                        <p x-show="qrError" class="text-xs text-red-500 mt-1 font-bold" x-text="qrError" style="display:none;"></p>
                                        <p x-show="verifyError" class="text-xs text-red-500 mt-1 font-bold" x-text="verifyError" style="display:none;"></p>
                                        
                                        <button type="button" x-show="qrUrl && !isVerified" @click="verifyPayment()" class="mt-3 w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow flex items-center justify-center gap-2 transition">
                                            <i class="fa-solid fa-rotate-right" :class="isVerifying ? 'fa-spin' : ''"></i> 
                                            <span x-text="isVerifying ? 'Verifying...' : 'Verify Payment Status'"></span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="bg-gray-50 border border-dashed border-gray-300 p-4 rounded-xl text-center mb-4">
                                <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-400 mb-2"></i>
                                <div class="text-xs text-gray-500 font-bold">Tap to record video</div>
                                <input type="file" name="video_file" accept="video/*" capture="environment" class="mt-2 text-xs w-full">
                            </div>
                            <input type="text" name="otp" placeholder="Enter Customer OTP (Optional)" class="w-full p-3 mb-4 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:border-yellow-500">
                            
                            <button type="submit" :disabled="payMode === 'UPI' && !isVerified" :class="(payMode === 'UPI' && !isVerified) ? 'opacity-50 cursor-not-allowed bg-gray-400' : 'bg-[#FFD700] hover:bg-yellow-500'" class="w-full py-4 text-gray-900 text-sm font-extrabold rounded-xl shadow-md transition">
                                <i class="fa-solid fa-truck-fast mr-2"></i> Complete Delivery
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('scanPayment', () => ({
            payMode: '',
            qrUrl: null,
            linkId: null,
            qrError: null,
            isVerifying: false,
            isVerified: false,
            verifyError: null,
            generateQr() {
                let amount = {{ $shipment->cod_amount > 0 ? $shipment->cod_amount : $shipment->invoice_value }};
                fetch('/rider/generate-cod-qr', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ awb_number: '{{ $shipment->awb_number }}', amount: amount })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        this.qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(data.link_url);
                        this.linkId = data.link_id;
                    } else {
                        this.qrError = data.message || 'Failed to generate QR';
                    }
                })
                .catch(err => {
                    this.qrError = 'Network error generating QR';
                });
            },
            verifyPayment() {
                if(!this.linkId) return;
                this.isVerifying = true;
                this.verifyError = null;
                fetch('/rider/verify-cod-qr', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ link_id: this.linkId })
                })
                .then(res => res.json())
                .then(data => {
                    this.isVerifying = false;
                    if(data.success) {
                        this.isVerified = true;
                        this.verifyError = null;
                    } else {
                        this.verifyError = data.message;
                    }
                })
                .catch(err => {
                    this.isVerifying = false;
                    this.verifyError = 'Network error checking payment';
                });
            }
        }));
    });
</script>
                                </div>
                            @endif

                            <button type="submit" class="w-full py-3 bg-[#FFD700] hover:bg-[#e6c200] text-gray-900 font-extrabold rounded-xl transition shadow-md">
                                <i class="fa-solid fa-check-circle mr-1"></i> 
                                {{ $shipment->is_cod ? 'Confirm Payment & Delivery' : 'Confirm Delivery' }}
                            </button>
                        </form>
                        
                                                <!-- NDR Action -->
                        <div class="mt-5 text-center pt-4 border-t border-yellow-200">
                            <button type="button" onclick="document.getElementById('ndrModal').classList.remove('hidden')" class="text-xs font-bold text-red-600 hover:text-red-800 transition uppercase tracking-wider flex items-center justify-center gap-1 w-full p-2 rounded-lg hover:bg-red-50">
                                <i class="fa-solid fa-triangle-exclamation"></i> Customer Unavailable? Mark as NDR
                            </button>
                        </div>
                    </div>
                @elseif($shipment->status === 'Delivered')
                    <div class="p-4 bg-green-50 text-center border-t border-green-100">
                        <i class="fa-solid fa-circle-check text-green-500 text-3xl mb-2"></i>
                        <h4 class="font-bold text-green-800">Shipment Delivered</h4>
                        <p class="text-xs text-green-600 mt-1">This shipment has already been successfully delivered.</p>
                    </div>
                @else
                    <div class="p-4 bg-gray-50 text-center border-t border-gray-100">
                        <p class="text-xs font-bold text-gray-600">No rider actions available for status: {{ $shipment->status }}</p>
                    </div>
                @endif
            </div>
        @else
            <div class="p-6 bg-red-50 rounded-3xl border border-red-100 text-center text-red-700">
                <i class="fa-solid fa-triangle-exclamation text-3xl mb-2"></i>
                <p class="font-bold text-sm">No shipment found with AWB: {{ $awb }}</p>
                <p class="text-xs mt-1 opacity-80">Please check the number and try again.</p>
            </div>
        @endif
    @endif
    @if($awb && $shipment && in_array($shipment->status, ['In Transit', 'Out for Delivery']))
    <!-- NDR Modal -->
    <div id="ndrModal" class="fixed inset-0 z-50 bg-black bg-opacity-60 hidden items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-black text-red-600 text-lg flex items-center"><i class="fa-solid fa-ban mr-2"></i> Mark as NDR</h3>
                <button type="button" onclick="document.getElementById('ndrModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            
            <p class="text-xs text-gray-500 mb-5 font-medium leading-relaxed">If the customer is unavailable, refused delivery, or the address is incorrect, please select the exact reason below to trigger a Non-Delivery Report (NDR).</p>
            
            <form action="{{ route('rider.ndr', $shipment->awb_number) }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-xs font-extrabold text-gray-900 uppercase tracking-wider mb-2">NDR Reason</label>
                    <select name="ndr_reason" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm font-bold focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 bg-gray-50">
                        <option value="">-- Select Reason --</option>
                        <option value="Customer Not Available">Customer Not Available / Door Locked</option>
                        <option value="Customer Refused to Accept">Customer Refused to Accept Delivery</option>
                        <option value="Address Incomplete/Incorrect">Address Incomplete or Incorrect</option>
                        <option value="Customer Out of Station">Customer Out of Station</option>
                        <option value="Fake Delivery Attempt">Fake Delivery Attempt / Rider Issue</option>
                        <option value="Consignee Shifted">Consignee Shifted</option>
                        <option value="Phone Switched Off/Not Reachable">Phone Switched Off / Not Reachable</option>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('ndrModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition text-sm">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-red-600 text-white rounded-xl font-extrabold shadow-md hover:bg-red-700 transition text-sm">Submit NDR</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
<style>@keyframes scan { from { top: 10%; } to { top: 90%; } }</style>
@endsection