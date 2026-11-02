@extends('layouts.public')

@section('title', 'OneStall Cargo | Leading Logistics & Courier Solutions')
@section('meta_description', 'OneStall Cargo is India\'s premier tech-enabled courier and logistics company. Seamless B2C express shipping, B2B cargo delivery, nationwide franchise hubs, and automated business shipping.')

@section('content')
<!-- 1. Hero Section -->
<section class="bg-gradient-to-r from-blue-50/40 via-white to-blue-50/40 relative pt-6 pb-8 overflow-hidden">
    <!-- Subtle background glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-100/50 rounded-full blur-3xl opacity-60 pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col lg:flex-row items-center">
        <!-- Left Side -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center text-center lg:text-left lg:pr-8">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-brand-navy leading-tight tracking-tight mb-1">
                {{ setting('hero_title1', 'eCommerce Shipping') }}
            </h1>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-brand-red leading-tight tracking-tight mb-4">
                {{ setting('hero_title2', 'Built for the Bold') }}
            </h1>
            <p class="text-base md:text-lg text-gray-700 mb-6 font-medium max-w-md mx-auto lg:mx-0 leading-relaxed">
                Fuel your ambition with next-gen eCommerce logistics - engineered for speed, scale, and simplicity.
            </p>

            <!-- CTA -->
            <div>
                <a href="{{ route('register') }}" class="inline-block bg-brand-red text-white font-bold text-base px-7 py-3 rounded-full hover:bg-brand-redHover transition-colors shadow-md shadow-red-500/20">
                    Get Started
                </a>
            </div>
        </div>

        <!-- Right Side (Images & Floating Elements) -->
        <div class="w-full lg:w-1/2 relative h-[320px] lg:h-[380px] flex justify-center items-center mt-8 lg:mt-0">
            
            <!-- Main Character Image (Clean Minimal Card) -->
            <div class="relative z-10 w-3/4 h-full rounded-2xl shadow-md border-2 border-white overflow-hidden bg-white">
                <img src="{{ asset('images/ecommerce_boy.jpg') }}" alt="eCommerce Logistics" class="w-full h-full object-cover object-center">
            </div>

            <!-- Floating UI Elements -->
            
            <!-- 1. Revenue Chart -->
            <div class="absolute top-2 left-2 lg:left-4 w-36 bg-white rounded-xl shadow-lg p-2.5 border border-gray-100 z-30">
                <h4 class="text-[10px] font-bold text-brand-navy text-center mb-1">REVENUE</h4>
                <div class="relative h-12 w-full border-l border-b border-gray-200 flex items-end">
                    <svg viewBox="0 0 100 50" class="absolute bottom-0 w-full h-full overflow-visible">
                        <path d="M0,50 L0,45 C20,40 30,20 50,25 C70,30 80,5 100,0 L100,50 Z" fill="#bbf7d0" opacity="0.6" />
                        <path d="M0,45 C20,40 30,20 50,25 C70,30 80,5 100,0" fill="none" stroke="#22c55e" stroke-width="2.5" />
                        <circle cx="50" cy="25" r="3" fill="#1e3a8a" />
                        <circle cx="100" cy="0" r="3" fill="#1e3a8a" />
                    </svg>
                </div>
            </div>

            <!-- 2. Store Mockup -->
            <div class="absolute bottom-2 right-2 lg:right-4 w-44 bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 z-30">
                <div class="h-4 bg-brand-navy flex justify-center items-center text-[5px] text-white font-bold tracking-widest">
                    <span>STORE</span>
                </div>
                <div class="p-2 flex gap-2">
                    <div class="w-1/2">
                        <div class="w-full h-12 bg-gray-100 rounded flex items-center justify-center text-gray-400">
                            <i class="fa-solid fa-headphones text-lg"></i>
                        </div>
                    </div>
                    <div class="w-1/2">
                        <div class="text-[7px] font-bold text-brand-navy mb-0.5">Headphone</div>
                        <div class="text-[7px] font-bold text-brand-red">&#8377; 2,999</div>
                    </div>
                </div>
            </div>

            <!-- 3. Floating Pill - Order Increase -->
            <div class="absolute bottom-16 left-0 bg-white rounded-lg shadow-md py-1 px-2.5 flex items-center gap-1.5 border border-gray-100 z-30">
                <div class="w-4 h-4 rounded bg-brand-navy flex items-center justify-center text-brand-red text-[9px]">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
                <span class="text-[9px] font-bold text-gray-800">5X Orders</span>
            </div>

            <!-- 4. Floating Pill - Live Tracking -->
            <div class="absolute top-8 right-2 bg-white rounded-lg shadow-md py-1 px-2.5 flex items-center gap-1.5 border border-gray-100 z-30">
                <div class="w-4 h-4 rounded border border-brand-red flex items-center justify-center text-brand-red text-[9px] bg-red-50">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <span class="text-[9px] font-bold text-gray-800">Live Tracking</span>
            </div>

            <!-- 5. Floating Pill - Support -->
            <div class="absolute top-24 right-0 bg-white rounded-lg shadow-md py-1 px-2.5 flex items-center gap-1.5 border border-gray-100 z-30">
                <div class="w-4 h-4 rounded border border-brand-navy flex items-center justify-center text-brand-navy text-[9px]">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <span class="text-[9px] font-bold text-gray-800">24/7 Support</span>
            </div>

        </div>
    </div>
</section>

<!-- 2. Dark Blue Status Bar -->
<section class="bg-brand-navy py-3 border-y border-brand-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-between items-center text-center gap-3">
            <div class="flex-1 min-w-[120px]">
                <div class="text-white font-medium text-xs md:text-sm">29,000+ Pin Codes</div>
            </div>
            <div class="hidden md:block w-px h-4 bg-gray-600"></div>
            <div class="flex-1 min-w-[120px]">
                <div class="text-white font-medium text-xs md:text-sm">Trusted Delivery Partners</div>
            </div>
            <div class="hidden md:block w-px h-4 bg-gray-600"></div>
            <div class="flex-1 min-w-[120px]">
                <div class="text-white font-medium text-xs md:text-sm">Automated NDR</div>
            </div>
            <div class="hidden md:block w-px h-4 bg-gray-600"></div>
            <div class="flex-1 min-w-[120px]">
                <div class="text-white font-medium text-xs md:text-sm">Fast COD Remittance</div>
            </div>
            <div class="hidden md:block w-px h-4 bg-gray-600"></div>
            <div class="flex-1 min-w-[120px]">
                <div class="text-white font-medium text-xs md:text-sm">Zero Setup Fee</div>
            </div>
        </div>
    </div>
</section>

<!-- 3. Alternating Feature Blocks -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-2xl md:text-3xl font-extrabold text-brand-navy">Scale With OneStall Cargo</h2>
            <p class="text-brand-red font-semibold mt-1 text-sm md:text-base">India's Most Trusted Shipping Platform</p>
        </div>

        <!-- Block 1 -->
        <div class="flex flex-col md:flex-row items-center gap-8 mb-12">
            <div class="w-full md:w-1/2">
                <div class="bg-gray-50 rounded-xl h-80 md:h-96 relative overflow-hidden border border-gray-100 p-2 shadow-sm">
                    <img src="{{ asset('images/dashboard.jpg') }}" alt="Logistics Dashboard" class="w-full h-full object-cover rounded-lg">
                </div>
            </div>
            <div class="w-full md:w-1/2">
                <h3 class="text-xl md:text-2xl font-bold text-brand-navy mb-3">Fastest COD Remittance</h3>
                <p class="text-gray-600 mb-4 text-sm md:text-base">Maintain unstoppable cash flow for your business. Get your COD payments credited to your account faster than ever before.</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">1-2 days remittance cycle</span></li>
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">Clear COD reconciliations</span></li>
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">Growth without cash blocks</span></li>
                </ul>
            </div>
        </div>

        <!-- Block 2 -->
        <div class="flex flex-col md:flex-row-reverse items-center gap-8 mb-12">
            <div class="w-full md:w-1/2">
                <div class="bg-gray-50 rounded-xl h-80 md:h-96 relative overflow-hidden border border-gray-100 p-2 shadow-sm">
                    <img src="{{ asset('images/warehouse.jpg') }}" alt="Warehouse Logistics" class="w-full h-full object-cover rounded-lg">
                </div>
            </div>
            <div class="w-full md:w-1/2">
                <h3 class="text-xl md:text-2xl font-bold text-brand-navy mb-3">Automated Courier Allocation</h3>
                <p class="text-gray-600 mb-4 text-sm md:text-base">Our system automatically allocates orders to the best courier partner based on historical performance, cost, and pin code serviceability.</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">Reduce shipping costs</span></li>
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">Increase delivery percentage</span></li>
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">Rule-based allocation</span></li>
                </ul>
            </div>
        </div>
        
        <!-- Block 3 -->
        <div class="flex flex-col md:flex-row items-center gap-8">
            <div class="w-full md:w-1/2">
                <div class="bg-gray-50 rounded-xl h-80 md:h-96 relative overflow-hidden border border-gray-100 p-2 shadow-sm">
                    <img src="{{ asset('images/dashboard.jpg') }}" alt="Data Analytics" class="w-full h-full object-cover rounded-lg">
                </div>
            </div>
            <div class="w-full md:w-1/2">
                <h3 class="text-xl md:text-2xl font-bold text-brand-navy mb-3">Minimize RTO Losses</h3>
                <p class="text-gray-600 mb-4 text-sm md:text-base">Reduce your Return To Origin (RTO) costs significantly with our automated Non-Delivery Report (NDR) management system.</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">Automated buyer communication (SMS/IVR)</span></li>
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">Real-time action on undelivered orders</span></li>
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-brand-red mr-2"></i> <span class="text-gray-700 font-medium">Fake remark detection</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- 4. Gradient API Section -->
<section class="py-12 bg-gradient-to-br from-[#E7004C] via-[#590C45] to-brand-navy text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col lg:flex-row items-center gap-8">
        <div class="w-full lg:w-1/2 text-center lg:text-left">
            <h2 class="text-2xl md:text-4xl font-extrabold mb-4 leading-tight">Seamless Integrations with Your Favorite Platforms</h2>
            <p class="text-sm md:text-base text-white/80 mb-6 font-medium">Sync orders directly from your store. Integrate in minutes with Shopify, WooCommerce, Magento, or use our developer-friendly APIs for custom setups.</p>
            <a href="{{ route('docs') }}" class="inline-block px-6 py-2.5 rounded-full bg-white text-brand-navy font-bold text-sm hover:bg-gray-100 transition shadow">
                View API Docs
            </a>
        </div>
        
        <div class="w-full lg:w-1/2 flex justify-center">
            <div class="relative w-64 h-64 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border border-white/20 animate-[spin_20s_linear_infinite]"></div>
                <div class="absolute inset-4 rounded-full border border-white/30 animate-[spin_15s_linear_infinite_reverse]"></div>
                
                <!-- Center Logo -->
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-lg z-10">
                    <span class="text-xl font-black text-brand-navy">API</span>
                </div>
                
                <!-- Satellite Icons -->
                <div class="absolute top-0 w-10 h-10 bg-[#95BF47] rounded-full flex items-center justify-center text-white shadow"><i class="fa-brands fa-shopify"></i></div>
                <div class="absolute bottom-0 w-10 h-10 bg-[#96588A] rounded-full flex items-center justify-center text-white shadow"><i class="fa-brands fa-wordpress"></i></div>
                <div class="absolute left-0 w-10 h-10 bg-[#F26522] rounded-full flex items-center justify-center text-white shadow"><i class="fa-brands fa-magento"></i></div>
                <div class="absolute right-0 w-10 h-10 bg-[#0073B1] rounded-full flex items-center justify-center text-white shadow"><i class="fa-solid fa-code"></i></div>
            </div>
        </div>
    </div>
</section>

<!-- 5. Integrations & Courier Logos (Scrolling Marquee) -->
<style>
    @keyframes marquee-left {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    @keyframes marquee-right {
        0% { transform: translateX(-50%); }
        100% { transform: translateX(0); }
    }
    .animate-marquee-left {
        animation: marquee-left 35s linear infinite;
        display: flex;
        width: max-content;
    }
    .animate-marquee-right {
        animation: marquee-right 35s linear infinite;
        display: flex;
        width: max-content;
    }
    .marquee-container:hover .animate-marquee-left,
    .marquee-container:hover .animate-marquee-right {
        animation-play-state: paused;
    }
    /* Hide scrollbar for the container */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<section class="py-12 bg-gray-50 border-b border-gray-100 overflow-hidden relative">
    <div class="text-center mb-8">
        <h2 class="text-2xl md:text-3xl font-extrabold text-brand-navy">Integrated With Your Favorite Platforms</h2>
        <p class="text-gray-500 font-medium mt-2">Connect your store and ship with India's top carriers in one click</p>
    </div>

    <!-- Integrations Row (Colorful Boxes) - Scrolling Left -->
    <div class="relative w-full overflow-hidden marquee-container mb-8">
        <!-- Fading Edges -->
        <div class="absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-gray-50 to-transparent z-10 pointer-events-none"></div>
        <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-gray-50 to-transparent z-10 pointer-events-none"></div>
        
        <div class="animate-marquee-left">
            @php
                $integrations = [
                    ['name' => 'Razorpay', 'icon' => 'fa-solid fa-credit-card', 'bg' => 'bg-[#3382FF]'],
                    ['name' => 'Instamojo', 'icon' => 'fa-solid fa-bolt', 'bg' => 'bg-[#1E194D]'],
                    ['name' => 'Magento', 'icon' => 'fa-brands fa-magento', 'bg' => 'bg-[#F26522]'],
                    ['name' => 'OpenCart', 'icon' => 'fa-solid fa-cart-shopping', 'bg' => 'bg-[#23A1D1]'],
                    ['name' => 'BigCommerce', 'icon' => 'fa-solid fa-store', 'bg' => 'bg-[#121118]'],
                    ['name' => 'Shopify', 'icon' => 'fa-brands fa-shopify', 'bg' => 'bg-[#95BF47]'],
                    ['name' => 'WooCommerce', 'icon' => 'fa-brands fa-wordpress', 'bg' => 'bg-[#96588A]'],
                    ['name' => 'WIX', 'icon' => 'fa-brands fa-wix', 'bg' => 'bg-black'],
                    ['name' => 'Etsy', 'icon' => 'fa-brands fa-etsy', 'bg' => 'bg-[#F56400]'],
                    ['name' => 'amazon', 'icon' => 'fa-brands fa-amazon', 'bg' => 'bg-[#232F3E]'],
                    ['name' => 'IndiaMart', 'icon' => 'fa-solid fa-bag-shopping', 'bg' => 'bg-[#911212]'],
                ];
            @endphp
            
            <!-- First Set -->
            <div class="flex items-center space-x-4 px-2">
                @foreach($integrations as $item)
                <div class="flex items-center justify-center {{ $item['bg'] }} text-white px-6 py-3.5 rounded-lg shadow-sm min-w-[170px] gap-2 transition transform hover:scale-105 cursor-pointer">
                    <i class="{{ $item['icon'] }} text-xl"></i> <span class="font-bold text-lg tracking-wide">{{ $item['name'] }}</span>
                </div>
                @endforeach
            </div>
            <!-- Second Set (Duplicate for seamless loop) -->
            <div class="flex items-center space-x-4 px-2">
                @foreach($integrations as $item)
                <div class="flex items-center justify-center {{ $item['bg'] }} text-white px-6 py-3.5 rounded-lg shadow-sm min-w-[170px] gap-2 transition transform hover:scale-105 cursor-pointer">
                    <i class="{{ $item['icon'] }} text-xl"></i> <span class="font-bold text-lg tracking-wide">{{ $item['name'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</section>


<!-- 6. Explore Platform Grid -->
<section class="py-12 bg-gray-50/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-2xl md:text-3xl font-extrabold text-brand-navy">Explore The OneStall Platform</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Large Card 1 -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow transition">
                <div class="inline-block px-2.5 py-0.5 rounded border border-blue-200 bg-blue-50 text-brand-navy text-[10px] font-bold mb-3 uppercase tracking-wide">
                    B2C Shipping
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Powering E-commerce Deliveries</h3>
                <p class="text-gray-600 text-sm mb-4">Ship to 29,000+ pin codes seamlessly. Give your customers a premium post-purchase tracking experience.</p>
                <div class="h-64 rounded-lg overflow-hidden border border-gray-100">
                    <img src="{{ asset('images/dashboard.jpg') }}" alt="B2C Shipping" class="w-full h-full object-cover">
                </div>
            </div>
            
            <!-- Large Card 2 -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow transition">
                <div class="inline-block px-2.5 py-0.5 rounded border border-blue-200 bg-blue-50 text-brand-navy text-[10px] font-bold mb-3 uppercase tracking-wide">
                    B2B & Cargo
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Heavy Shipments Made Easy</h3>
                <p class="text-gray-600 text-sm mb-4">Move bulk inventory across warehouses or to offline distributors with our dedicated B2B cargo services at discounted rates.</p>
                <div class="h-64 rounded-lg overflow-hidden border border-gray-100">
                    <img src="{{ asset('images/warehouse.jpg') }}" alt="B2B Cargo" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Small Card 1 -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow transition flex flex-col h-full">
                <div class="inline-block px-2 py-0.5 rounded border border-blue-200 bg-blue-50 text-brand-navy text-[9px] font-bold mb-2 uppercase self-start">Global Reach</div>
                <h4 class="text-base font-bold text-gray-900 mb-1">Cross Border</h4>
                <p class="text-xs text-gray-600 mb-4 flex-grow">Ship internationally with ease. Export your products worldwide.</p>
                <div class="h-20 bg-gray-50 rounded-lg flex items-center justify-center text-2xl text-gray-400 border border-gray-100">
                    <i class="fa-solid fa-globe"></i>
                </div>
            </div>

            <!-- Small Card 2 -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow transition flex flex-col h-full">
                <div class="inline-block px-2 py-0.5 rounded border border-blue-200 bg-blue-50 text-brand-navy text-[9px] font-bold mb-2 uppercase self-start">Tech Logistics</div>
                <h4 class="text-base font-bold text-gray-900 mb-1">Video Evidence</h4>
                <p class="text-xs text-gray-600 mb-4 flex-grow">Record pickups and hub scans to eliminate fake damage claims.</p>
                <div class="h-20 bg-gray-50 rounded-lg flex items-center justify-center text-2xl text-gray-400 border border-gray-100">
                    <i class="fa-solid fa-video"></i>
                </div>
            </div>

            <!-- Small Card 3 -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow transition flex flex-col h-full">
                <div class="inline-block px-2 py-0.5 rounded border border-blue-200 bg-blue-50 text-brand-navy text-[9px] font-bold mb-2 uppercase self-start">Smart Warehousing</div>
                <h4 class="text-base font-bold text-gray-900 mb-1">Fulfillment</h4>
                <p class="text-xs text-gray-600 mb-4 flex-grow">Store products closer to customers. Same-day & Next-day delivery.</p>
                <div class="h-20 bg-gray-50 rounded-lg flex items-center justify-center text-2xl text-gray-400 border border-gray-100">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
            </div>
            
            <!-- Small Card 4 -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow transition flex flex-col h-full">
                <div class="inline-block px-2 py-0.5 rounded border border-blue-200 bg-blue-50 text-brand-navy text-[9px] font-bold mb-2 uppercase self-start">Custom Tech</div>
                <h4 class="text-base font-bold text-gray-900 mb-1">White Label</h4>
                <p class="text-xs text-gray-600 mb-4 flex-grow">Branded tracking pages and custom logistics infrastructure.</p>
                <div class="h-20 bg-gray-50 rounded-lg flex items-center justify-center text-2xl text-gray-400 border border-gray-100">
                    <i class="fa-solid fa-code-branch"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 7. Mobile App Section (Google Play Store Only) -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gray-50 rounded-2xl p-6 md:p-10 border border-gray-200 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="w-full md:w-1/2">
                <h2 class="text-2xl md:text-3xl font-extrabold text-brand-navy mb-3">Track Orders On The Go</h2>
                <p class="text-sm md:text-base text-gray-600 mb-6">Download our Android app to manage shipments, track NDRs, and monitor your business metrics from anywhere.</p>
                
                <!-- Google Play Store Only Button -->
                <div class="flex items-center gap-4">
                    <a href="#" class="h-12 px-5 bg-black rounded-lg flex items-center justify-center text-white hover:bg-gray-800 transition shadow-md">
                        <i class="fa-brands fa-google-play text-2xl mr-3 text-green-400"></i>
                        <div class="text-left leading-tight">
                            <div class="text-[9px] uppercase tracking-wider text-gray-300">Get it on</div>
                            <div class="font-bold text-sm">Google Play</div>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="w-full md:w-1/2 flex justify-center">
                <div class="w-64 h-[420px] rounded-[1.8rem] shadow-xl relative overflow-hidden border-4 border-gray-800">
                    <img src="{{ asset('images/mobile_app.jpg') }}" alt="Mobile Tracking App" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. Final B2B Cargo Banner -->
<section class="py-12 bg-gradient-to-r from-brand-navy to-brand-blue text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row items-center gap-8">
            <div class="w-full lg:w-1/2">
                <div class="w-full h-72 lg:h-80 rounded-xl overflow-hidden shadow-xl relative">
                    <img src="{{ asset('images/warehouse.jpg') }}" alt="B2B Express Freight" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-brand-navy/50 flex items-center justify-center">
                        <div class="text-lg font-bold tracking-widest text-white backdrop-blur-sm px-5 py-2 rounded border border-white/30">B2B EXPRESS FREIGHT</div>
                    </div>
                </div>
            </div>
            
            <div class="w-full lg:w-1/2 text-center lg:text-left">
                <h2 class="text-2xl md:text-3xl font-extrabold mb-3">Heavy Shipments? No Problem.</h2>
                <p class="text-sm md:text-base text-white/80 mb-6">Transport heavy and bulk shipments across India with our B2B cargo network. Get discounted freight rates and real-time tracking for LTL and FTL shipments.</p>
                <a href="{{ route('solutions.b2b') }}" class="inline-block px-7 py-3 rounded-full bg-brand-red text-white font-bold text-sm hover:bg-brand-redHover transition shadow-md">
                    Explore B2B Cargo
                </a>
            </div>
        </div>
    </div>
</section>

@endsection

