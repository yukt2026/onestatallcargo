@extends('layouts.hub')
@section('title', 'Add Order - OneStall Cargo')

@section('content')
@php
    $oldNames = old('product_name');
    $oldPrices = old('product_price');
    $oldQtys = old('product_qty');
    $oldSkus = old('product_sku');
    
    $initialProducts = [];
    if (is_array($oldNames) && count($oldNames) > 0) {
        foreach ($oldNames as $idx => $val) {
            $initialProducts[] = [
                'name' => (string) ($val ?? ''),
                'price' => is_array($oldPrices) ? (float) ($oldPrices[$idx] ?? 0) : 0,
                'qty' => is_array($oldQtys) ? (int) ($oldQtys[$idx] ?? 1) : 1,
                'sku' => is_array($oldSkus) ? (string) ($oldSkus[$idx] ?? '') : '',
                'hsn' => ''
            ];
        }
    } elseif (isset($shipment) && !empty($shipment->product_details)) {
        $decoded = json_decode($shipment->product_details, true);
        if (is_array($decoded) && count($decoded) > 0) {
            foreach ($decoded as $item) {
                $initialProducts[] = [
                    'name' => $item['name'] ?? '',
                    'price' => (float) ($item['price'] ?? 0),
                    'qty' => (int) ($item['qty'] ?? 1),
                    'sku' => $item['sku'] ?? '',
                    'hsn' => ''
                ];
            }
        }
    }
    
    if (empty($initialProducts)) {
        $initialProducts[] = [
            'name' => isset($shipment) ? ($shipment->product_name ?? '') : '',
            'price' => isset($shipment) ? (float) ($shipment->invoice_value ?? 0) : 0,
            'qty' => isset($shipment) ? (int) ($shipment->product_qty ?? 1) : 1,
            'sku' => isset($shipment) ? ($shipment->product_sku ?? '') : '',
            'hsn' => ''
        ];
    }
    
    $initialWeight = old('weight_kg', isset($shipment) ? $shipment->weight_kg : 0.5);
    $initialCod = old('is_cod', isset($shipment) ? $shipment->is_cod : '0');
    $initialType = old('shipment_type', isset($shipment) ? $shipment->shipment_type : 'B2C');
    
    $warehouses = \App\Models\Warehouse::where('user_id', Auth::id())->get();
    $defaultWh = $warehouses->where('is_default', true)->first() ?? $warehouses->first();
@endphp

<div class="max-w-[1200px] mx-auto pb-24" x-data="bookingForm()">
    
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Add Order</h1>
        <div class="text-[11px] font-semibold text-gray-500">
            <a href="{{ route('hub.dashboard') }}" class="hover:text-[#4338ca] transition">Dashboard</a> 
            <span class="mx-1">/</span> 
            <span class="text-gray-900">Add Order</span>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 rounded bg-red-50 text-red-700 border-l-4 border-red-500 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif
    @if(isset($errors) && $errors->any())
        <div class="mb-6 p-4 rounded bg-red-50 text-red-700 border-l-4 border-red-500 text-sm">
            <p class="font-bold mb-2">Please fix the following errors:</p>
            <ul class="list-disc pl-5 font-medium space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('hub.shipments.store') }}" method="POST" id="mainBookingForm">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode ?? 'create' }}">
        <input type="hidden" name="shipment_id" value="{{ ($mode ?? '') === 'edit' ? ($shipment->id ?? '') : '' }}">
        <input type="hidden" name="invoice_value" :value="totalOrderValue()">
        <input type="hidden" name="shipment_type" :value="shipmentType">
        <input type="hidden" name="is_cod" :value="paymentMode === 'COD' ? '1' : '0'">
        <input type="hidden" name="payment_mode" :value="paymentMode">
        <input type="hidden" name="cod_amount" :value="paymentMode === 'COD' ? totalOrderValue() : ''">
        <input type="hidden" name="ship_now" value="1">
        
        <!-- Custom Tabs mimicking BigShip -->
        <div class="flex border-b border-gray-200 mb-6 bg-transparent">
            <button type="button" @click="shipmentType = 'B2C'" :class="shipmentType === 'B2C' ? 'text-indigo-900 border-b-2 border-indigo-900 font-bold' : 'text-gray-500 font-semibold'" class="px-6 py-3 text-[13px] transition">
                <i class="fa-solid fa-box mr-1 text-emerald-600"></i> Domestic (B2C)
            </button>
            <button type="button" @click="shipmentType = 'B2B'" :class="shipmentType === 'B2B' ? 'text-indigo-900 border-b-2 border-indigo-900 font-bold' : 'text-gray-500 font-semibold'" class="px-6 py-3 text-[13px] transition">
                <i class="fa-solid fa-truck-moving mr-1 text-blue-600"></i> B2B Cargo & Freight
            </button>
            <button type="button" @click="shipmentType = 'International'; paymentMode = 'Prepaid'" :class="shipmentType === 'International' ? 'text-indigo-900 border-b-2 border-indigo-900 font-bold' : 'text-gray-500 font-semibold'" class="px-6 py-3 text-[13px] transition">
                <i class="fa-solid fa-globe mr-1 text-purple-600"></i> International
            </button>
        </div>
        
        <div class="bg-white rounded p-6 shadow-sm mb-6">
            <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <div class="text-red-500 text-[11px] font-semibold">*All Fields Required</div>
                    <div class="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-950">
                        <i class="fa-solid fa-wallet text-amber-500"></i>
                        <span>Wallet Balance: &#8377;{{ number_format(Auth::user()->wallet_balance ?? 0, 2) }}</span>
                        <a href="{{ route('hub.wallet.index') }}" target="_blank" class="underline text-indigo-700 hover:text-indigo-900 ml-1 font-extrabold">+ Recharge</a>
                    </div>
                </div>
                <a href="{{ route('hub.bulk') }}" class="bg-[#10b981] hover:bg-[#059669] text-white text-[12px] font-bold px-4 py-2 rounded shadow-sm transition">
                    Bulk Order
                </a>
            </div>

            <!-- SECTION 1: Order Information -->
            <div class="flex items-center gap-3 mb-4">
                <div class="w-7 h-7 rounded-full bg-[#1e1b4b] text-white flex items-center justify-center font-bold text-sm">1</div>
                <h3 class="text-[15px] font-bold text-[#1e1b4b]">Order Information</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pl-2 md:pl-10 mb-10">
                <div class="relative">
                    <div class="flex justify-between items-end mb-1">
                        <label class="text-[11px] font-semibold text-gray-500">Order ID / Invoice No<span class="text-red-500">*</span></label>
                        <button type="button" @click="generateOrderId()" class="text-[10px] text-blue-500 font-semibold hover:underline">Auto Generate</button>
                    </div>
                    <div class="relative">
                        <input type="text" name="order_id" x-model="orderId" placeholder="Enter Order/Invoice No" required :class="orderId ? 'border-gray-200 focus:border-indigo-500' : 'border-red-300 focus:border-red-500'" class="w-full px-3 py-2 border rounded text-xs outline-none">
                        <i x-show="!orderId" class="fa-solid fa-circle-exclamation text-red-500 absolute right-3 top-1/2 -translate-y-1/2 text-[10px]"></i>
                    </div>
                    <p x-show="!orderId" class="text-[9px] text-red-500 mt-1">* This Field is Required</p>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Order Date <span class="text-red-500">*</span></label>
                    <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none text-gray-700">
                </div>
            </div>

            <!-- SECTION 2: Receiver Information -->
            <div class="flex items-center gap-3 mb-4">
                <div class="w-7 h-7 rounded-full bg-[#1e1b4b] text-white flex items-center justify-center font-bold text-sm">2</div>
                <h3 class="text-[15px] font-bold text-[#1e1b4b]">Receiver Information</h3>
            </div>
            
            <div class="pl-2 md:pl-10 mb-10">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Email Id</label>
                        <input type="email" name="receiver_email" placeholder="Enter email id" class="w-full px-3 py-2 border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Full Name/Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="receiver_name" value="{{ old('receiver_name', $shipment->receiver_name ?? '') }}" required placeholder="Enter Full Name/Co" class="w-full px-3 py-2 border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Mobile No. <span class="text-red-500">*</span></label>
                        <input type="text" name="receiver_phone" value="{{ old('receiver_phone', $shipment->receiver_phone ?? '') }}" required placeholder="Enter Mobile no" class="w-full px-3 py-2 border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none">
                    </div>
                    <div class="row-span-2">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Address<span class="text-red-500">*</span></label>
                        <textarea name="delivery_address" required placeholder="House No./ Ward Number, Building Name" rows="4" class="w-full px-3 py-2 border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none resize-none">{{ old('delivery_address', $shipment->delivery_address ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Landmark</label>
                        <input type="text" name="delivery_landmark" placeholder="Enter the Landmark" class="w-full px-3 py-2 border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1" x-text="shipmentType === 'International' ? 'Zipcode / Pincode *' : 'Pincode *'">Pincode <span class="text-red-500">*</span></label>
                        <input type="text" name="delivery_pincode" x-model="deliveryPincode" @input="fetchCityState()" required placeholder="Enter the Pincode" class="w-full px-3 py-2 border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1" x-text="shipmentType === 'International' ? 'State / Province' : 'State'">State</label>
                        <input type="text" name="delivery_state" x-model="deliveryState" placeholder="Enter the state Name" class="w-full px-3 py-2 bg-white border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none text-gray-700">
                    </div>
                    <!-- Spacer for correct grid layout -->
                    <div class="col-start-1 md:col-start-3 md:row-start-2 -mt-10 md:mt-0 opacity-0 pointer-events-none md:opacity-100 md:pointer-events-auto invisible md:visible"></div>
                    <div class="col-start-1 md:col-start-4 -mt-[48px] md:mt-0">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">City</label>
                        <input type="text" name="delivery_city" x-model="deliveryCity" placeholder="Enter the city Name" class="w-full px-3 py-2 bg-white border border-gray-200 focus:border-indigo-500 rounded text-xs outline-none text-gray-700">
                    </div>
                    
                    <!-- International Fields (Appended to the grid dynamically) -->
                    <div x-show="shipmentType === 'International'" class="col-span-1 md:col-span-2 pt-2 transition">
                        <label class="block text-[11px] font-semibold text-indigo-900 mb-1">Destination Country <span class="text-red-500">*</span></label>
                        <input type="text" list="country_list" name="destination_country" placeholder="Select or type country" :required="shipmentType === 'International'" class="w-full px-3 py-2 border border-indigo-200 bg-indigo-50 focus:border-indigo-500 rounded text-xs outline-none text-gray-700 shadow-sm">
                        <datalist id="country_list">
                            <option value="United States">
                            <option value="United Kingdom">
                            <option value="United Arab Emirates">
                            <option value="Canada">
                            <option value="Australia">
                            <option value="Germany">
                            <option value="France">
                            <option value="Singapore">
                            <option value="Saudi Arabia">
                        </datalist>
                    </div>
                    <div x-show="shipmentType === 'International'" class="pt-2 transition">
                        <label class="block text-[11px] font-semibold text-indigo-900 mb-1">Customs Value (₹) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="customs_value" placeholder="Total Value" :required="shipmentType === 'International'" class="w-full px-3 py-2 border border-indigo-200 bg-indigo-50 focus:border-indigo-500 rounded text-xs outline-none text-gray-700 shadow-sm">
                    </div>
                    <div x-show="shipmentType === 'International'" class="pt-2 transition">
                        <label class="block text-[11px] font-semibold text-indigo-900 mb-1">HS Code</label>
                        <input type="text" name="hs_code" placeholder="e.g. 6109.10" class="w-full px-3 py-2 border border-indigo-200 bg-indigo-50 focus:border-indigo-500 rounded text-xs outline-none text-gray-700 shadow-sm">
                    </div>

                    <!-- B2B Cargo Fields -->
                    <div x-show="shipmentType === 'B2B'" class="pt-2 transition">
                        <label class="block text-[11px] font-semibold text-indigo-900 mb-1">Vehicle / Load Type <span class="text-red-500">*</span></label>
                        <select name="vehicle_type" class="w-full px-3 py-2 border border-indigo-200 bg-indigo-50 focus:border-indigo-500 rounded text-xs outline-none text-gray-700 shadow-sm font-semibold">
                            <option value="PTL" {{ old('vehicle_type', $shipment->vehicle_type ?? '') == 'PTL' ? 'selected' : '' }}>PTL (Part Truck Load - Commercial Freight)</option>
                            <option value="FTL" {{ old('vehicle_type', $shipment->vehicle_type ?? '') == 'FTL' ? 'selected' : '' }}>FTL (Full Truck Load)</option>
                            <option value="Tata Ace" {{ old('vehicle_type', $shipment->vehicle_type ?? '') == 'Tata Ace' ? 'selected' : '' }}>Tata Ace (1.5 Ton Mini Truck)</option>
                            <option value="Pickup 8ft" {{ old('vehicle_type', $shipment->vehicle_type ?? '') == 'Pickup 8ft' ? 'selected' : '' }}>Pickup 8ft (2.0 Ton)</option>
                            <option value="14ft Eicher" {{ old('vehicle_type', $shipment->vehicle_type ?? '') == '14ft Eicher' ? 'selected' : '' }}>14ft Eicher (4.0 Ton)</option>
                            <option value="19ft Truck" {{ old('vehicle_type', $shipment->vehicle_type ?? '') == '19ft Truck' ? 'selected' : '' }}>19ft Truck (7.0 Ton)</option>
                            <option value="32ft Container" {{ old('vehicle_type', $shipment->vehicle_type ?? '') == '32ft Container' ? 'selected' : '' }}>32ft Multi-Axle Container</option>
                        </select>
                    </div>
                    <div x-show="shipmentType === 'B2B'" class="pt-2 transition">
                        <label class="block text-[11px] font-semibold text-indigo-900 mb-1">E-Way Bill Number</label>
                        <input type="text" name="eway_bill_number" value="{{ old('eway_bill_number', $shipment->eway_bill_number ?? '') }}" placeholder="12-digit E-Way Bill (e.g. 123456789012)" maxlength="50" class="w-full px-3 py-2 border border-indigo-200 bg-indigo-50 focus:border-indigo-500 rounded text-xs outline-none text-gray-700 shadow-sm">
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Box Dimension Detail -->
            <div class="flex items-center gap-3 mb-4">
                <div class="w-7 h-7 rounded-full bg-[#1e1b4b] text-white flex items-center justify-center font-bold text-sm">3</div>
                <h3 class="text-[15px] font-bold text-[#1e1b4b]">Box Dimension Detail</h3>
            </div>
            
            <div class="pl-2 md:pl-10 mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <input type="checkbox" class="w-3.5 h-3.5 text-[#4338ca] rounded border-gray-300">
                    <label class="text-[11px] italic font-semibold text-gray-600">Multi Box Shipment</label>
                </div>
                
                <div class="w-48 mb-6">
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Payment Mode <span class="text-red-500">*</span></label>
                    <select name="payment_mode" x-model="paymentMode" :disabled="shipmentType === 'International'" class="w-full px-3 py-2 border border-gray-200 rounded text-xs outline-none focus:border-indigo-500 bg-white disabled:bg-gray-100 disabled:text-gray-500">
                        <option value="Prepaid">Prepaid</option>
                        <option value="COD">COD</option>
                    </select>
                </div>

                <div class="inline-block bg-yellow-400 text-white text-[11px] font-bold px-5 py-1.5 rounded-full mb-5 shadow-sm">
                    Box Details
                </div>

                <div class="w-full overflow-x-auto pb-2 mb-5">
                    <div class="flex items-start gap-2 sm:gap-4 min-w-[320px]">
                        <!-- Weight -->
                        <div class="flex-1 min-w-[70px]">
                            <span class="block text-[10px] font-bold text-gray-500 mb-1 whitespace-nowrap">Box Weight <span class="text-red-500">*</span></span>
                            <div class="relative">
                                <input type="number" step="0.01" name="weight_kg" x-model.number="deadWeight" class="w-full h-10 border border-gray-200 rounded-lg pl-2 pr-7 text-xs font-bold text-gray-800 bg-white focus:outline-none focus:border-indigo-500 text-center shadow-sm">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-bold pointer-events-none">KG</span>
                            </div>
                        </div>

                        <!-- Length -->
                        <div class="flex-1 min-w-[70px]">
                            <span class="block text-[10px] font-bold text-gray-500 mb-1 whitespace-nowrap">Length <span class="text-red-500">*</span></span>
                            <div class="relative">
                                <input type="number" name="length_cm" x-model.number="lengthCm" class="w-full h-10 border border-gray-200 rounded-lg pl-2 pr-7 text-xs font-bold text-gray-800 bg-white focus:outline-none focus:border-indigo-500 text-center shadow-sm">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-bold pointer-events-none">CM</span>
                            </div>
                        </div>

                        <!-- Breadth / Width -->
                        <div class="flex-1 min-w-[70px]">
                            <span class="block text-[10px] font-bold text-gray-500 mb-1 whitespace-nowrap">Breadth <span class="text-red-500">*</span></span>
                            <div class="relative">
                                <input type="number" name="width_cm" x-model.number="widthCm" class="w-full h-10 border border-gray-200 rounded-lg pl-2 pr-7 text-xs font-bold text-gray-800 bg-white focus:outline-none focus:border-indigo-500 text-center shadow-sm">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-bold pointer-events-none">CM</span>
                            </div>
                        </div>

                        <!-- Height -->
                        <div class="flex-1 min-w-[70px]">
                            <span class="block text-[10px] font-bold text-gray-500 mb-1 whitespace-nowrap">Height <span class="text-red-500">*</span></span>
                            <div class="relative">
                                <input type="number" name="height_cm" x-model.number="heightCm" class="w-full h-10 border border-gray-200 rounded-lg pl-2 pr-7 text-xs font-bold text-gray-800 bg-white focus:outline-none focus:border-indigo-500 text-center shadow-sm">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-bold pointer-events-none">CM</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full overflow-x-auto pb-2">
                    <div class="min-w-[800px]">
                        <!-- Products Table Headers -->
                        <div class="grid gap-3 mb-1" :class="paymentMode === 'COD' ? 'grid-cols-7' : 'grid-cols-6'">
                            <div class="col-span-2 text-[10px] font-bold text-gray-500">Product Name <span class="text-red-500">*</span></div>
                            <div class="text-[10px] font-bold text-gray-500">Category <span class="text-red-500">*</span></div>
                            <div class="text-[10px] font-bold text-gray-500">HSN Code</div>
                            <div class="text-[10px] font-bold text-gray-500">Quantity <span class="text-red-500">*</span></div>
                            <div class="text-[10px] font-bold text-gray-500">Amount <span class="text-red-500">*</span></div>
                            <div class="text-[10px] font-bold text-gray-500" x-show="paymentMode === 'COD'">Collectable Amount <span class="text-red-500">*</span></div>
                        </div>

                        <template x-for="(product, index) in products" :key="index">
                            <div class="grid gap-3 mb-3 items-center" :class="paymentMode === 'COD' ? 'grid-cols-7' : 'grid-cols-6'">
                                <div class="col-span-2">
                                    <input type="text" name="product_name[]" x-model="product.name" class="w-full h-10 px-3 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 shadow-sm">
                                </div>
                                <div>
                                    <select class="w-full h-10 px-3 border border-gray-200 rounded-lg text-xs outline-none text-gray-600 focus:border-indigo-500 shadow-sm">
                                        <option>Select Category</option>
                                        <option>Apparel</option>
                                        <option>Electronics</option>
                                        <option>Health & Beauty</option>
                                    </select>
                                </div>
                                <div>
                                    <input type="text" x-model="product.hsn" class="w-full h-10 px-3 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 shadow-sm">
                                </div>
                                <div>
                                    <input type="number" min="1" name="product_qty[]" x-model.number="product.qty" class="w-full h-10 px-3 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 shadow-sm">
                                </div>
                                <div>
                                    <input type="number" step="0.01" name="product_price[]" x-model.number="product.price" class="w-full h-10 px-3 border border-gray-200 rounded-lg text-xs outline-none focus:border-indigo-500 shadow-sm">
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1" x-show="paymentMode === 'COD'">
                                        <input type="number" step="0.01" :value="product.price * product.qty" readonly class="w-full h-10 px-3 bg-gray-50 border border-gray-200 rounded-lg text-xs outline-none text-gray-500 shadow-sm">
                                    </div>
                                    <button type="button" @click="addProduct" x-show="index === products.length - 1" class="w-8 h-8 rounded-lg bg-[#8bc34a] hover:bg-[#7cb342] text-white flex items-center justify-center shrink-0 shadow-sm transition">
                                        <i class="fa-solid fa-plus text-sm"></i>
                                    </button>
                                    <button type="button" @click="removeProduct(index)" x-show="products.length > 1" class="w-8 h-8 rounded-lg bg-red-500 hover:bg-red-600 text-white flex items-center justify-center shrink-0 shadow-sm transition">
                                        <i class="fa-solid fa-minus text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Pickup Location -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="w-7 h-7 rounded-full bg-[#1e1b4b] text-white flex items-center justify-center font-bold text-sm">4</div>
                    <h3 class="text-[15px] font-bold text-[#1e1b4b]">Pickup Location</h3>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full md:w-auto">
                    <span class="text-[11px] font-semibold text-gray-600">Search Pickup Location</span>
                    <div class="relative w-full sm:w-64">
                        <div class="absolute left-0 top-0 bottom-0 w-8 bg-gray-100 border border-gray-200 rounded-l flex items-center justify-center text-gray-500">
                            <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                        </div>
                        <input type="text" x-model="warehouseSearch" placeholder="Search by Warehouse Name/City Name/Pincode" class="w-full pl-10 pr-3 py-1.5 border border-gray-200 rounded text-[10px] outline-none focus:border-indigo-500">
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openQuickWarehouseModal()" class="bg-[#1e1b4b] hover:bg-indigo-950 text-white px-3 py-1.5 rounded text-[11px] font-bold flex items-center gap-1.5 transition shadow-sm">
                            <i class="fa-solid fa-plus text-[10px]"></i> Add Warehouse
                        </button>
                        <a href="{{ route('hub.profile') }}?view=warehouses" target="_blank" class="text-[10px] text-gray-500 hover:text-indigo-600 underline font-semibold">
                            Manage All
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="pl-2 md:pl-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[250px] overflow-y-auto pr-2 mb-4">
                    <template x-for="wh in warehouseList" :key="wh.id">
                        <label @click="selectWarehouseItem(wh)" x-show="!warehouseSearch || (wh.name + ' ' + wh.city + ' ' + wh.pincode).toLowerCase().includes(warehouseSearch.toLowerCase())" class="border rounded p-4 cursor-pointer transition hover:border-[#1e1b4b] flex items-center gap-4" :class="selectedWarehouse == wh.id ? 'border-[#1e1b4b] bg-[#f8f9ff]' : 'border-gray-200'">
                            <input type="radio" name="pickup_location" :value="wh.id" x-model="selectedWarehouse" class="hidden">
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0" :class="selectedWarehouse == wh.id ? 'border-[#1e1b4b]' : 'border-gray-300'">
                                <div class="w-2 h-2 bg-[#1e1b4b] rounded-full" x-show="selectedWarehouse == wh.id"></div>
                            </div>
                            <div class="flex flex-col w-full text-[11px]">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-start sm:items-center">
                                    <div class="font-bold text-gray-900 text-center flex items-center justify-center" x-text="wh.name"></div>
                                    <div class="text-center text-gray-600 border-l border-r border-gray-200 flex flex-col justify-center">
                                        <span class="block text-gray-400">Contact Person</span>
                                        <span class="font-bold text-gray-800" x-text="wh.phone"></span>
                                    </div>
                                    <div class="text-center text-gray-500 flex flex-col items-center justify-center px-2">
                                        <span class="block truncate w-full" :title="wh.address" x-text="wh.address"></span>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </template>
                    <div x-show="warehouseList.length === 0" class="col-span-2 text-center py-6 text-sm text-gray-500 font-semibold bg-gray-50 rounded">
                        No warehouses found. Please add a warehouse or enter pickup details below.
                    </div>
                </div>

                <!-- Explicit Pickup Details for This Shipment -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-3">
                        <span class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-location-dot text-indigo-600"></i> Pickup / Shipper Address Details
                        </span>
                        <span class="text-[10px] text-gray-500 italic">Select from above or type new pickup address & mobile number</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">Pickup / Shipper Name <span class="text-red-500">*</span></label>
                            <input type="text" name="pickup_name" x-model="pickupName" placeholder="e.g. Sagar Restaurant" class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs outline-none focus:border-indigo-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">Pickup Contact Mobile (10 Digits) <span class="text-red-500">*</span></label>
                            <input type="tel" name="pickup_phone" x-model="pickupPhone" placeholder="e.g. 8789796492" maxlength="10" class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs outline-none focus:border-indigo-500 bg-white">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="block text-[10px] font-bold text-gray-700 mb-1">Pickup Address (Shop / Street / Area) <span class="text-red-500">*</span></label>
                        <textarea name="pickup_address" x-model="pickupAddress" rows="2" placeholder="e.g. Sagar Restaurant Gudedekote Road" class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs outline-none focus:border-indigo-500 resize-none bg-white"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">Pickup Pincode (6 Digits) <span class="text-red-500">*</span></label>
                            <input type="text" name="pickup_pincode" x-model="pickupPincode" @input="fetchPickupPincode()" maxlength="6" placeholder="e.g. 583135" class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs outline-none focus:border-indigo-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                            <input type="text" name="pickup_city" x-model="pickupCity" placeholder="City" class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs outline-none focus:border-indigo-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">State <span class="text-red-500">*</span></label>
                            <input type="text" name="pickup_state" x-model="pickupState" placeholder="State" class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs outline-none focus:border-indigo-500 bg-white">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mt-3 pt-2 border-t border-gray-200">
                        <input type="checkbox" name="save_to_warehouses" value="1" id="saveWhCheck" class="w-3.5 h-3.5 text-indigo-600 rounded border-gray-300">
                        <label for="saveWhCheck" class="text-[11px] font-semibold text-gray-700 cursor-pointer">
                            Save this pickup address to my warehouses list for future orders
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-2 py-2">
                    <input type="checkbox" checked class="w-3.5 h-3.5 text-[#007bff] rounded border-gray-300">
                    <label class="text-[11px] italic font-semibold text-gray-600">Return address will be the same as pickup address</label>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-center gap-4 mt-8 pt-4">
                <button type="reset" class="px-8 py-2 bg-[#f3f4f6] hover:bg-gray-200 text-gray-700 text-xs font-bold rounded">
                    RESET
                </button>
                <button type="submit" class="px-8 py-2 bg-[#007bff] hover:bg-blue-600 text-white text-xs font-bold rounded shadow-sm">
                    SAVE ORDER
                </button>
            </div>
            
        </div>
    </form>

    <!-- Modal: Quick Add Warehouse for Hub -->
    <div x-show="showAddWarehouseModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-transition.opacity>
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100" @click.away="showAddWarehouseModal = false">
            <div class="px-6 py-4 bg-[#1e1b4b] text-white flex justify-between items-center">
                <h3 class="font-bold text-sm">Add New Pickup Warehouse</h3>
                <button type="button" @click="showAddWarehouseModal = false" class="text-gray-300 hover:text-white">
                    <i class="fa-solid fa-xmark fa-lg"></i>
                </button>
            </div>

            <form @submit.prevent="saveQuickWarehouse()" class="p-6 space-y-4">
                <div x-show="quickWhError" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-xl border border-red-200" x-text="quickWhError"></div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Warehouse Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="quickWh.name" placeholder="e.g. Primary Hub Warehouse" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Contact Person <span class="text-red-500">*</span></label>
                        <input type="text" x-model="quickWh.contact_person" placeholder="Manager Name" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Mobile Number (10 Digits) <span class="text-red-500">*</span></label>
                        <input type="tel" x-model="quickWh.phone" placeholder="9876543210" maxlength="10" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Full Address <span class="text-red-500">*</span></label>
                    <textarea x-model="quickWh.address" rows="3" placeholder="Plot No, Street, Landmark" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs outline-none focus:border-indigo-500 resize-none"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Pincode (6 Digits) <span class="text-red-500">*</span></label>
                        <input type="text" x-model="quickWh.pincode" @input="fetchQuickWhPincode()" maxlength="6" placeholder="110001" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                        <input type="text" x-model="quickWh.city" placeholder="City" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">State <span class="text-red-500">*</span></label>
                        <input type="text" x-model="quickWh.state" placeholder="State" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="flex justify-end items-center gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showAddWarehouseModal = false" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="quickWhLoading" class="px-5 py-2 bg-[#1e1b4b] hover:bg-indigo-950 text-white rounded-xl text-xs font-bold shadow transition flex items-center gap-2 disabled:opacity-50">
                        <span x-show="quickWhLoading"><i class="fa-solid fa-spinner fa-spin"></i></span>
                        <span>Save & Select</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function bookingForm() {
    return {
        shipmentType: @json($initialType),
        paymentMode: @json($initialCod === '1' || $initialCod === true ? 'COD' : 'Prepaid'),
        warehouseSearch: '',
        orderId: '{{ old('order_id') }}',
        deliveryPincode: '{{ old('delivery_pincode', $shipment->delivery_pincode ?? '') }}',
        deliveryCity: '{{ old('delivery_city', $shipment->delivery_city ?? '') }}',
        deliveryState: '{{ old('delivery_state', $shipment->delivery_state ?? '') }}',
        warehouseList: {!! json_encode($warehouses) !!},
        selectedWarehouse: {{ $warehouses->where('is_default', true)->first()->id ?? ($warehouses->first()->id ?? 'null') }},
        pickupName: '{{ old('pickup_name', $shipment->pickup_name ?? ($defaultWh->name ?? '')) }}',
        pickupPhone: '{{ old('pickup_phone', $shipment->pickup_phone ?? ($defaultWh->phone ?? '')) }}',
        pickupAddress: '{{ old('pickup_address', $shipment->pickup_address ?? ($defaultWh->address ?? '')) }}',
        pickupPincode: '{{ old('pickup_pincode', $shipment->pickup_pincode ?? ($defaultWh->pincode ?? '')) }}',
        pickupCity: '{{ old('pickup_city', $shipment->pickup_city ?? ($defaultWh->city ?? '')) }}',
        pickupState: '{{ old('pickup_state', $shipment->pickup_state ?? ($defaultWh->state ?? '')) }}',
        selectWarehouseItem(wh) {
            this.selectedWarehouse = wh.id;
            this.pickupName = wh.name || wh.contact_person || '';
            this.pickupPhone = wh.phone || '';
            this.pickupAddress = wh.address || '';
            this.pickupCity = wh.city || '';
            this.pickupState = wh.state || '';
            this.pickupPincode = wh.pincode || '';
        },
        async fetchPickupPincode() {
            if (this.pickupPincode && this.pickupPincode.length === 6) {
                try {
                    const res = await fetch('https://api.postalpincode.in/pincode/' + this.pickupPincode);
                    const data = await res.json();
                    if (data && data[0] && data[0].Status === 'Success') {
                        this.pickupCity = data[0].PostOffice[0].District;
                        this.pickupState = data[0].PostOffice[0].State;
                    }
                } catch(e) {
                    console.error('Pickup pincode lookup error:', e);
                }
            }
        },
        showAddWarehouseModal: false,
        quickWhLoading: false,
        quickWhError: '',
        quickWh: {
            name: '',
            contact_person: '',
            phone: '',
            address: '',
            pincode: '',
            city: '',
            state: ''
        },
        openQuickWarehouseModal() {
            this.quickWhError = '';
            this.quickWh = {
                name: '',
                contact_person: '',
                phone: '',
                address: '',
                pincode: '',
                city: '',
                state: ''
            };
            this.showAddWarehouseModal = true;
        },
        async fetchQuickWhPincode() {
            if (this.quickWh.pincode && this.quickWh.pincode.length === 6) {
                try {
                    const res = await fetch('https://api.postalpincode.in/pincode/' + this.quickWh.pincode);
                    const data = await res.json();
                    if (data && data[0] && data[0].Status === 'Success') {
                        this.quickWh.city = data[0].PostOffice[0].District;
                        this.quickWh.state = data[0].PostOffice[0].State;
                    }
                } catch(e) {
                    console.error('Pincode lookup error:', e);
                }
            }
        },
        async saveQuickWarehouse() {
            this.quickWhLoading = true;
            this.quickWhError = '';
            try {
                const res = await fetch('{{ route('hub.warehouses.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.quickWh)
                });
                const data = await res.json();
                if (res.ok && data.success && data.warehouse) {
                    this.warehouseList.unshift(data.warehouse);
                    this.selectWarehouseItem(data.warehouse);
                    this.showAddWarehouseModal = false;
                } else {
                    let msg = data.message || 'Failed to save warehouse';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join(' ');
                    }
                    this.quickWhError = msg;
                }
            } catch(e) {
                this.quickWhError = 'Error: ' + e.message;
            } finally {
                this.quickWhLoading = false;
            }
        },
        deadWeight: {{ $initialWeight }},
        lengthCm: 10,
        widthCm: 10,
        heightCm: 10,
        products: {!! json_encode($initialProducts) !!},
        generateOrderId() {
            this.orderId = 'ORD-' + Math.floor(100000 + Math.random() * 900000);
        },
        async fetchCityState() {
            // Do not hit Indian Postal API for International Shipments
            if (this.shipmentType === 'International') return;
            
            if (this.deliveryPincode && this.deliveryPincode.toString().length === 6) {
                try {
                    const response = await fetch('https://api.postalpincode.in/pincode/' + this.deliveryPincode);
                    const data = await response.json();
                    if (data && data[0].Status === 'Success') {
                        this.deliveryCity = data[0].PostOffice[0].District;
                        this.deliveryState = data[0].PostOffice[0].State;
                    }
                } catch (error) {
                    console.error('Error fetching pincode:', error);
                }
            }
        },
        addProduct() {
            this.products.push({ name: '', price: 0, qty: 1, sku: '', hsn: '' });
        },
        removeProduct(index) {
            if(this.products.length > 1) {
                this.products.splice(index, 1);
            }
        },
        totalOrderValue() {
            return this.products.reduce((total, p) => total + (parseFloat(p.price) || 0) * (parseInt(p.qty) || 1), 0);
        }
    }
}
</script>
@endsection