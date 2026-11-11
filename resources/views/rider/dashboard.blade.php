@extends('layouts.rider')

@section('content')
<div x-data="riderDashboard()" x-init="initDashboard()">
    <!-- Audio Element -->
    <audio id="assignmentRingtone" loop>
        <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
    </audio>

    <!-- Tabs -->
    <div class="flex bg-gray-200 p-1 rounded-xl mb-4">
        <button @click="tab = 'deliveries'" :class="tab == 'deliveries' ? 'bg-white shadow text-[#1e293b]' : 'text-gray-500'" class="flex-1 py-2 text-sm font-bold rounded-lg transition relative">Deliveries <span x-show="pendingDeliveries.length > 0" class="absolute -top-2 -right-1 bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full" x-text="pendingDeliveries.length" style="display:none;"></span></button>
        <button @click="tab = 'pickups'" :class="tab == 'pickups' ? 'bg-white shadow text-[#1e293b]' : 'text-gray-500'" class="flex-1 py-2 text-sm font-bold rounded-lg transition relative">Pickups <span x-show="pendingPickups.length > 0" class="absolute -top-2 -right-1 bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full" x-text="pendingPickups.length" style="display:none;"></span></button>
    </div>

    <!-- Alarm Banner -->
    <div x-show="hasUnaccepted" class="bg-red-600 text-white p-3 rounded-xl mb-4 flex items-center justify-between shadow-lg animate-pulse" style="display: none;">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-bell-ringing text-2xl"></i>
            <div>
                <h4 class="font-bold text-sm">New Orders Assigned!</h4>
                <p class="text-[10px] opacity-90">Please accept them to stop the alarm.</p>
            </div>
        </div>
    </div>
    
    <!-- Deliveries List -->
    <div x-show="tab == 'deliveries'" class="space-y-4">
        <template x-for="del in pendingDeliveries" :key="del.awb_number">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 relative overflow-hidden" :class="!isAccepted(del.awb_number) ? 'ring-2 ring-red-500 ring-offset-2' : ''">
            <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
            <div class="flex justify-between items-start mb-3">
                <div>
                    <div class="font-extrabold text-gray-900" x-text="del.receiver_name"></div>
                    <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold" x-text="del.awb_number"></div>
                </div>
                <div class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-1 rounded shadow-sm" :class="!isAccepted(del.awb_number) ? 'bg-red-100 text-red-700 animate-pulse' : ''" x-text="!isAccepted(del.awb_number) ? 'New Assignment' : 'Delivery'"></div>
            </div>
            <div class="text-xs text-gray-600 mb-3"><i class="fa-solid fa-location-dot w-4"></i> <span x-text="del.delivery_address + ', ' + del.delivery_city"></span></div>
            
                        <template x-if="del.is_cod">
            <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 text-xs font-bold p-2 rounded flex justify-between">
                <span>Collect COD:</span><span x-text="'&#8377;' + (del.cod_amount > 0 ? del.cod_amount : del.invoice_value)"></span>
            </div>
            </template>
            
            <template x-if="del.is_cod && isAccepted(del.awb_number) && del.showUpload">
            <div class="p-3 border border-yellow-200 rounded-xl bg-white shadow-sm mb-3">
                <label class="block text-xs font-extrabold text-gray-900 uppercase tracking-wider mb-2">Payment Mode</label>
                <select required x-model="del.selected_payment" class="w-full px-4 py-2 border border-gray-300 rounded-xl text-sm font-bold bg-gray-50 mb-2">
                    <option value="">-- Select Payment Mode --</option>
                    <option value="Cash">Cash Collected</option>
                    <option value="UPI">UPI / QR Code Scan</option>
                </select>

                <!-- Dynamic Cashfree QR Code -->
                <div x-show="del.selected_payment === 'UPI'" x-init="$watch('del.selected_payment', val => { if(val === 'UPI' && !del.qr_url) generateQr(del) })" class="mt-3 text-center p-3 bg-gray-50 border border-gray-200 rounded-xl animate-fade-in" style="display:none;">
                    <p class="text-xs font-bold text-gray-600 mb-2" x-show="!del.is_verified">Ask Customer to Scan Cashfree QR</p>
                    <div class="bg-white p-2 inline-block rounded-xl shadow-sm border border-gray-100 min-h-[150px]" :class="del.is_verified ? 'border-green-500 bg-green-50' : ''">
                        
                        <div x-show="del.is_verified" style="display:none;" class="w-32 h-32 flex flex-col items-center justify-center text-green-600">
                            <i class="fa-solid fa-circle-check text-4xl mb-2"></i>
                            <span class="text-xs font-extrabold">PAID</span>
                        </div>

                        <img x-show="del.qr_url && !del.is_verified" :src="del.qr_url" alt="UPI QR Code" class="w-32 h-32 mx-auto" style="display:none;">
                        <div x-show="!del.qr_url && !del.is_verified" class="w-32 h-32 mx-auto flex flex-col items-center justify-center text-[10px] text-gray-500">
                            <i class="fa-solid fa-spinner fa-spin text-xl mb-2 text-indigo-500"></i>
                            Generating...
                        </div>
                    </div>
                    <p x-show="del.qr_error" class="text-xs text-red-500 mt-1 font-bold" x-text="del.qr_error" style="display:none;"></p>
                    <p x-show="del.verify_error" class="text-xs text-red-500 mt-1 font-bold" x-text="del.verify_error" style="display:none;"></p>
                    
                    <button type="button" x-show="del.qr_url && !del.is_verified" @click="verifyPayment(del)" class="mt-3 w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow flex items-center justify-center gap-2 transition" style="display:none;">
                        <i class="fa-solid fa-rotate-right" :class="del.is_verifying ? 'fa-spin' : ''"></i> 
                        <span x-text="del.is_verifying ? 'Verifying...' : 'Verify Payment Status'"></span>
                    </button>
                </div>
            </div>
            </template>

            <!-- Unaccepted State -->
            <button x-show="!isAccepted(del.awb_number)" @click="acceptOrder(del.awb_number)" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-extrabold rounded-xl shadow-md transition animate-bounce mt-2"><i class="fa-solid fa-check-double mr-2"></i> Accept Delivery Order</button>

            <!-- Accepted State -->
            <div x-show="isAccepted(del.awb_number)" style="display:none;">
                <button @click="del.showUpload = !del.showUpload" class="w-full py-3 bg-[#1e293b] text-white text-sm font-bold rounded-xl shadow-md"><i class="fa-solid fa-camera mr-2"></i> Deliver & Upload Proof</button>
                
                <form x-show="del.showUpload" action="{{ route('rider.evidence.upload') }}" method="POST" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-gray-100 space-y-3" style="display: none;">
                    @csrf
                    <input type="hidden" name="awb_number" :value="del.awb_number">
                    <input type="hidden" name="action_type" value="Delivery">
                    
                    <div class="bg-gray-50 border border-dashed border-gray-300 p-4 rounded-xl text-center">
                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-400 mb-2"></i>
                        <div class="text-xs text-gray-500 font-bold">Tap to record video</div>
                        <input type="file" name="video_file" accept="video/*" capture="environment" class="mt-2 text-xs w-full">
                    </div>
                    <input type="text" name="otp" placeholder="Enter Customer OTP (Optional)" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none">
                                    <button type="submit" :disabled="del.is_cod && del.selected_payment === 'UPI' && !del.is_verified" :class="(del.is_cod && del.selected_payment === 'UPI' && !del.is_verified) ? 'opacity-50 cursor-not-allowed bg-gray-400' : 'bg-[#FFD700] hover:bg-yellow-500'" class="w-full py-3 text-gray-900 text-sm font-extrabold rounded-xl shadow-md transition">Complete Delivery</button>
                <input type="hidden" name="payment_mode" :value="del.selected_payment">
                </form>
            </div>
        </div>
        </template>
        
        <div x-show="pendingDeliveries.length === 0" class="text-center p-8 text-gray-400" style="display:none;">
            <i class="fa-solid fa-box-open text-4xl mb-2 text-gray-300"></i>
            <p class="font-bold text-sm">No pending deliveries!</p>
        </div>
    </div>

    <!-- Pickups List -->
    <div x-show="tab == 'pickups'" class="space-y-4" style="display: none;">
        <template x-for="pickup in pendingPickups" :key="pickup.awb_number">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 relative overflow-hidden" :class="!isAccepted(pickup.awb_number) ? 'ring-2 ring-red-500 ring-offset-2' : ''">
            <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
            <div class="flex justify-between items-start mb-3">
                <div>
                    <div class="font-extrabold text-gray-900" x-text="pickup.seller_name"></div>
                    <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold" x-text="pickup.awb_number"></div>
                </div>
                <div class="bg-purple-100 text-purple-700 text-[10px] font-bold px-2 py-1 rounded shadow-sm" :class="!isAccepted(pickup.awb_number) ? 'bg-red-100 text-red-700 animate-pulse' : ''" x-text="!isAccepted(pickup.awb_number) ? 'New Assignment' : 'Pickup'"></div>
            </div>
            <div class="text-xs text-gray-600 mb-3"><i class="fa-solid fa-location-dot w-4"></i> <span x-text="(pickup.pickup_address || 'Origin Address') + (pickup.pickup_city ? ', ' + pickup.pickup_city : '') + (pickup.pickup_pincode ? ' - ' + pickup.pickup_pincode : '')"></span></div>
            
            <!-- Unaccepted State -->
            <button x-show="!isAccepted(pickup.awb_number)" @click="acceptOrder(pickup.awb_number)" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-extrabold rounded-xl shadow-md transition animate-bounce mt-2"><i class="fa-solid fa-check-double mr-2"></i> Accept Pickup Order</button>

            <!-- Accepted State -->
            <div x-show="isAccepted(pickup.awb_number)" style="display:none;">
                <button @click="pickup.showUpload = !pickup.showUpload" class="w-full py-3 bg-[#1e293b] text-white text-sm font-bold rounded-xl shadow-md"><i class="fa-solid fa-camera mr-2"></i> Pickup & Upload Proof</button>
                
                <form x-show="pickup.showUpload" action="{{ route('rider.evidence.upload') }}" method="POST" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-gray-100 space-y-3" style="display: none;">
                    @csrf
                    <input type="hidden" name="awb_number" :value="pickup.awb_number">
                    <input type="hidden" name="action_type" value="Pickup">
                    
                    <div class="bg-gray-50 border border-dashed border-gray-300 p-4 rounded-xl text-center">
                        <i class="fa-solid fa-video text-2xl text-gray-400 mb-2"></i>
                        <div class="text-xs text-gray-500 font-bold">Record parcel condition</div>
                        <input type="file" name="video_file" accept="video/*" capture="environment" class="mt-2 text-xs w-full">
                    </div>
                                    <button type="submit" :disabled="del.is_cod && del.selected_payment === 'UPI' && !del.is_verified" :class="(del.is_cod && del.selected_payment === 'UPI' && !del.is_verified) ? 'opacity-50 cursor-not-allowed bg-gray-400' : 'bg-[#FFD700] hover:bg-yellow-500'" class="w-full py-3 text-gray-900 text-sm font-extrabold rounded-xl shadow-md transition">Complete Delivery</button>
                <input type="hidden" name="payment_mode" :value="del.selected_payment">
                </form>
            </div>
        </div>
        </template>

        <div x-show="pendingPickups.length === 0" class="text-center p-8 text-gray-400" style="display:none;">
            <i class="fa-solid fa-truck-ramp-box text-4xl mb-2 text-gray-300"></i>
            <p class="font-bold text-sm">No pending pickups!</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('riderDashboard', () => ({
        tab: 'deliveries',
        pendingDeliveries: [],
        pendingPickups: [],
        acceptedOrders: JSON.parse(localStorage.getItem('riderAcceptedOrders') || '[]'),
        hasUnaccepted: false,
        ringtone: null,
        
                generateQr(order) {
            let amount = order.cod_amount > 0 ? order.cod_amount : order.invoice_value;
            fetch('/rider/generate-cod-qr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ awb_number: order.awb_number, amount: amount })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    order.qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(data.link_url);
                    order.link_id = data.link_id;
                } else {
                    order.qr_error = data.message || 'Failed to generate QR';
                }
            })
            .catch(err => {
                order.qr_error = 'Network error generating QR';
            });
        },
        verifyPayment(order) {
            if(!order.link_id) return;
            order.is_verifying = true;
            order.verify_error = null;
            fetch('/rider/verify-cod-qr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ link_id: order.link_id })
            })
            .then(res => res.json())
            .then(data => {
                order.is_verifying = false;
                if(data.success) {
                    order.is_verified = true;
                    order.verify_error = null;
                } else {
                    order.verify_error = data.message;
                }
            })
            .catch(err => {
                order.is_verifying = false;
                order.verify_error = 'Network error checking payment';
            });
        },
        initDashboard() {
            let rawDeliveries = @json($pendingDeliveries);
            let rawPickups = @json($pendingPickups);
            
            this.pendingDeliveries = rawDeliveries.map(d => ({ ...d, showUpload: false, selected_payment: '', qr_url: null, link_id: null, qr_error: null, is_verifying: false, is_verified: false, verify_error: null }));
            this.pendingPickups = rawPickups.map(p => ({ 
                ...p, 
                showUpload: false,
                seller_name: (p.pickup_name && p.pickup_name.trim() !== '') ? p.pickup_name : (p.user ? (p.user.company_name || p.user.name) : 'Seller')
            }));
            
            if (this.pendingDeliveries.length === 0 && this.pendingPickups.length > 0) {
                this.tab = 'pickups';
            }
            
            this.ringtone = document.getElementById('assignmentRingtone');
            this.checkAlarms();
            
            document.body.addEventListener('click', () => {
                if (this.hasUnaccepted && this.ringtone.paused) {
                    this.ringtone.play().catch(e => console.log('Audio play blocked:', e));
                }
            }, { once: true });
            
            if (this.hasUnaccepted) {
                setTimeout(() => { this.ringtone.play().catch(e => console.log('Auto-play blocked')); }, 500);
            }
        },
        
        isAccepted(awb) {
            return this.acceptedOrders.includes(awb);
        },
        
        acceptOrder(awb) {
            if (!this.acceptedOrders.includes(awb)) {
                this.acceptedOrders.push(awb);
                localStorage.setItem('riderAcceptedOrders', JSON.stringify(this.acceptedOrders));
            }
            this.checkAlarms();
        },
        
        checkAlarms() {
            let foundUnaccepted = false;
            
            this.pendingDeliveries.forEach(d => {
                if (!this.isAccepted(d.awb_number)) foundUnaccepted = true;
            });
            
            this.pendingPickups.forEach(p => {
                if (!this.isAccepted(p.awb_number)) foundUnaccepted = true;
            });
            
            this.hasUnaccepted = foundUnaccepted;
            
            if (this.ringtone) {
                if (this.hasUnaccepted) {
                    this.ringtone.play().catch(e => console.log('Audio blocked', e));
                } else {
                    this.ringtone.pause();
                    this.ringtone.currentTime = 0;
                }
            }
        }
    }));
});
</script>
@endsection