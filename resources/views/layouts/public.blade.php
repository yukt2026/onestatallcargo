<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', setting('site_name', 'OneStall Cargo') . ' | ' . setting('site_tagline', 'Leading Logistics & Courier Company'))</title>
    <meta name="description" content="@yield('meta_description', setting('meta_description', 'OneStall Cargo is India\'s premier tech-enabled courier and logistics company covering B2C express shipping, B2B/Cargo, international delivery, franchise hub networks, and API integrations.'))">
    
    <!-- Open Graph / WhatsApp / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ setting('site_name', 'OneStall Cargo') }}">
    <meta property="og:title" content="@yield('title', setting('site_name', 'OneStall Cargo') . ' | ' . setting('site_tagline', 'Shipping Solutions Designed To Help You Grow'))">
    <meta property="og:description" content="@yield('meta_description', setting('meta_description', 'OneStall Cargo is India\'s premier tech-enabled courier and logistics company covering B2C express shipping, B2B/Cargo, international delivery, franchise hub networks, and API integrations.'))">
    <meta property="og:image" content="{{ asset(setting('og_image', 'images/og-preview.jpg')) }}">
    <meta property="og:image:secure_url" content="{{ asset(setting('og_image', 'images/og-preview.jpg')) }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ setting('site_name', 'OneStall Cargo') }}">

    <!-- Twitter / X Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', setting('site_name', 'OneStall Cargo'))">
    <meta name="twitter:description" content="@yield('meta_description', setting('meta_description', 'OneStall Cargo is India\'s premier tech-enabled courier and logistics company covering B2C express shipping, B2B/Cargo, international delivery, franchise hub networks, and API integrations.'))">
    <meta name="twitter:image" content="{{ asset(setting('og_image', 'images/og-preview.jpg')) }}">
    
    <!-- Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS (via CDN with custom config) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            navy: '#091024',
                            blue: '#111D3D',
                            red: '#E7004C',
                            redHover: '#C70041',
                        }
                    }
                }
            }
        }
    </script>
    <!-- AlpineJS for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .hero-gradient { background: linear-gradient(135deg, #001960 0%, #021A62 100%); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <!-- Global Navigation -->
    <header class="bg-white text-brand-navy sticky top-0 z-50 shadow-sm border-b border-gray-100" x-data="{ mobileMenu: false, activeDropdown: null }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="/" class="flex items-center group  transition-transform group-hover:scale-105">
                        <img src="{{ asset('images/logo.jpg') }}" alt="OneStall Cargo" class="h-16 md:h-20 w-auto object-contain py-1">
                    </a>
                </div>

                <!-- Desktop Menu -->
                <nav class="hidden lg:flex items-center space-x-8">
                    
                    <!-- Solutions Dropdown -->
                    <div class="relative" @mouseenter="activeDropdown = 'solutions'" @mouseleave="activeDropdown = null">
                        <button class="text-sm font-semibold text-gray-700 hover:text-brand-red flex items-center py-8">
                            Solutions <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i>
                        </button>
                        <div x-show="activeDropdown === 'solutions'" x-transition x-cloak class="absolute left-0 mt-0 w-64 bg-white text-gray-800 rounded-b-lg shadow-xl border-t-2 border-brand-yellow overflow-hidden">
                            <a href="{{ route('solutions.b2c') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-box text-brand-blue w-5"></i> B2C Shipping</a>
                            <a href="{{ route('solutions.b2b') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-truck-moving text-brand-blue w-5"></i> B2B & Cargo</a>
                            <a href="{{ route('solutions.international') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-plane text-brand-blue w-5"></i> International Shipping</a>


                            <a href="{{ route('solutions.ecommerce') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50"><i class="fa-solid fa-store text-brand-blue w-5"></i> E-commerce Integration</a>
                        </div>
                    </div>

                    <!-- Platform Dropdown -->
                    <div class="relative" @mouseenter="activeDropdown = 'platform'" @mouseleave="activeDropdown = null">
                        <button class="text-sm font-semibold text-gray-700 hover:text-brand-red flex items-center py-8">
                            Platform <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i>
                        </button>
                        <div x-show="activeDropdown === 'platform'" x-transition x-cloak class="absolute left-0 mt-0 w-64 bg-white text-gray-800 rounded-b-lg shadow-xl border-t-2 border-brand-yellow overflow-hidden">
                            <a href="{{ route('platform.tracking') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-location-crosshairs text-brand-blue w-5"></i> Shipment Tracking</a>
                            <a href="{{ route('platform.evidence') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-video text-brand-blue w-5"></i> Video Evidence</a>
                            <a href="{{ route('platform.ndr') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-phone-volume text-brand-blue w-5"></i> NDR Management</a>
                            <a href="{{ route('platform.rto') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-arrow-rotate-left text-brand-blue w-5"></i> RTO Management</a>
                            <a href="{{ route('platform.cod') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-indian-rupee-sign text-brand-blue w-5"></i> COD & Settlement</a>
                            <a href="{{ route('platform.awb') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50"><i class="fa-solid fa-barcode text-brand-blue w-5"></i> AWB & Labels</a>
                        </div>
                    </div>

                    <!-- Developers Dropdown -->
                    <div class="relative" @mouseenter="activeDropdown = 'developers'" @mouseleave="activeDropdown = null">
                        <button class="text-sm font-semibold text-gray-700 hover:text-brand-red flex items-center py-8">
                            Developers <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i>
                        </button>
                        <div x-show="activeDropdown === 'developers'" x-transition x-cloak class="absolute left-0 mt-0 w-56 bg-white text-gray-800 rounded-b-lg shadow-xl border-t-2 border-brand-yellow overflow-hidden">
                            <a href="{{ route('developers') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-code text-brand-blue w-5"></i> API Platform</a>
                            <a href="{{ route('docs') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50"><i class="fa-solid fa-book text-brand-blue w-5"></i> API Documentation</a>
                        </div>
                    </div>

                    <!-- Business Dropdown -->
                    <div class="relative" @mouseenter="activeDropdown = 'business'" @mouseleave="activeDropdown = null">
                        <button class="text-sm font-semibold text-gray-700 hover:text-brand-red flex items-center py-8">
                            Business <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i>
                        </button>
                        <div x-show="activeDropdown === 'business'" x-transition x-cloak class="absolute left-0 mt-0 w-48 bg-white text-gray-800 rounded-b-lg shadow-xl border-t-2 border-brand-yellow overflow-hidden">
                            <a href="{{ route('pricing') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-tags text-brand-blue w-5"></i> Pricing</a>
                            <a href="{{ route('franchise') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-store-alt text-brand-blue w-5"></i> Franchise</a>
                            <a href="{{ route('corporate') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-building text-brand-blue w-5"></i> Corporate</a>
                            <a href="{{ route('partners') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50"><i class="fa-solid fa-handshake text-brand-blue w-5"></i> Partners</a>
                        </div>
                    </div>

                    <!-- Resources Dropdown -->
                    <div class="relative" @mouseenter="activeDropdown = 'resources'" @mouseleave="activeDropdown = null">
                        <button class="text-sm font-semibold text-gray-700 hover:text-brand-red flex items-center py-8">
                            Resources <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i>
                        </button>
                        <div x-show="activeDropdown === 'resources'" x-transition x-cloak class="absolute left-0 mt-0 w-48 bg-white text-gray-800 rounded-b-lg shadow-xl border-t-2 border-brand-yellow overflow-hidden">
                            <a href="{{ route('about') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-circle-info text-brand-blue w-5"></i> About Us</a>
                            <a href="{{ route('faq') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-circle-question text-brand-blue w-5"></i> FAQ</a>
                            <a href="{{ route('help') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50 border-b border-gray-100"><i class="fa-solid fa-life-ring text-brand-blue w-5"></i> Help Center</a>
                            <a href="{{ route('contact') }}" class="block px-5 py-3 text-sm font-medium hover:bg-gray-50"><i class="fa-solid fa-address-book text-brand-blue w-5"></i> Contact Us</a>
                        </div>
                    </div>
                </nav>

                <!-- Desktop CTAs -->
                <div class="hidden lg:flex items-center space-x-4">
                    <a href="{{ route('track') }}" class="text-sm font-semibold text-gray-700 hover:text-brand-red transition">
                        Track Order
                    </a>
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-full px-6 py-2 hover:bg-gray-50 transition ml-4">Login</a>
                    <a href="{{ route('register') }}" class="px-6 py-2 rounded-full text-sm font-bold bg-brand-red text-white hover:bg-brand-redHover transition shadow-sm ml-2">Sign Up</a>
                </div>

                <!-- Mobile menu button -->
                <div class="lg:hidden flex items-center">
                    <button @click="mobileMenu = !mobileMenu" class="text-gray-700 hover:text-brand-red focus:outline-none">
                        <i class="fa-solid fa-bars text-2xl" x-show="!mobileMenu"></i>
                        <i class="fa-solid fa-xmark text-2xl" x-show="mobileMenu" x-cloak></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div x-show="mobileMenu" x-transition x-cloak class="lg:hidden bg-brand-blue border-t border-gray-700 h-screen overflow-y-auto">
            <div class="px-4 pt-4 pb-32 space-y-1">
                <!-- Solutions -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" class="w-full flex justify-between items-center px-3 py-3 text-base font-semibold text-white hover:bg-gray-800 rounded">
                        Solutions <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <div x-show="open" class="pl-6 space-y-2 mt-1">
                        <a href="{{ route('solutions.b2c') }}" class="block py-2 text-sm text-gray-300">B2C Shipping</a>
                        <a href="{{ route('solutions.b2b') }}" class="block py-2 text-sm text-gray-300">B2B & Cargo</a>
                        <a href="{{ route('solutions.international') }}" class="block py-2 text-sm text-gray-300">International Shipping</a>


                        <a href="{{ route('solutions.ecommerce') }}" class="block py-2 text-sm text-gray-300">E-commerce Integration</a>
                    </div>
                </div>
                
                <!-- Platform -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" class="w-full flex justify-between items-center px-3 py-3 text-base font-semibold text-white hover:bg-gray-800 rounded">
                        Platform <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <div x-show="open" class="pl-6 space-y-2 mt-1">
                        <a href="{{ route('platform.tracking') }}" class="block py-2 text-sm text-gray-300">Shipment Tracking</a>
                        <a href="{{ route('platform.evidence') }}" class="block py-2 text-sm text-gray-300">Video Evidence</a>
                        <a href="{{ route('platform.ndr') }}" class="block py-2 text-sm text-gray-300">NDR Management</a>
                        <a href="{{ route('platform.rto') }}" class="block py-2 text-sm text-gray-300">RTO Management</a>
                        <a href="{{ route('platform.cod') }}" class="block py-2 text-sm text-gray-300">COD & Settlement</a>
                    </div>
                </div>

                <a href="{{ route('developers') }}" class="block px-3 py-3 text-base font-semibold text-white hover:bg-gray-800 rounded">Developers</a>
                <a href="{{ route('pricing') }}" class="block px-3 py-3 text-base font-semibold text-white hover:bg-gray-800 rounded">Pricing</a>
                <a href="{{ route('contact') }}" class="block px-3 py-3 text-base font-semibold text-white hover:bg-gray-800 rounded">Contact Us</a>
                
                <div class="border-t border-gray-600 my-4"></div>
                <a href="{{ route('track') }}" class="block px-3 py-3 text-base font-bold text-brand-red">Track Shipment</a>
                <a href="{{ route('login') }}" class="block px-3 py-3 text-base font-semibold text-white">Login</a>
                <a href="{{ route('register') }}" class="block px-3 py-3 text-base font-bold text-white bg-brand-red text-brand-navy rounded mt-2 text-center">Get Started</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow w-full overflow-hidden">
        @yield('content')
    </main>

    <!-- Global Footer -->
    <footer class="bg-gray-900 text-gray-400 py-8 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
                <!-- Column 1 -->
                <div class="lg:col-span-1">
                    <div class="mb-6 bg-white inline-block p-1.5 rounded-lg">
                        <img src="{{ asset('images/logo.jpg') }}" alt="OneStall Cargo" class="h-12 w-auto">
                    </div>
                    <p class="text-sm leading-relaxed mb-4">Complete logistics infrastructure for businesses. Ship B2B, B2C, and internationally on one platform.</p>
                    
                    <!-- Registered Company Info -->
                    <div class="mt-4 space-y-2 text-xs text-gray-500 leading-relaxed border-t border-gray-800 pt-4">
                        <p class="font-semibold text-gray-400 uppercase tracking-wider text-[10px]">Registered Company</p>
                        <p class="font-bold text-gray-300">ANYURVA AYURVEDA PRIVATE LIMITED</p>
                        <p>CIN: U21003UP2024PTC205462</p>
                        <p>569/153 KHA, Barigawan, KP Road,<br>32 BN PAC, Lucknow – 226023, U.P.</p>
                    </div>
                </div>

                <!-- Column 2 -->
                <div>
                    <h3 class="text-white font-semibold mb-5 text-sm uppercase tracking-wider">Solutions</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="{{ route('solutions.b2c') }}" class="hover:text-white transition">B2C Shipping</a></li>
                        <li><a href="{{ route('solutions.b2b') }}" class="hover:text-white transition">B2B & Cargo</a></li>
                        <li><a href="{{ route('solutions.international') }}" class="hover:text-white transition">International Shipping</a></li>


                        <li><a href="{{ route('solutions.ecommerce') }}" class="hover:text-white transition">E-commerce Integration</a></li>
                    </ul>
                </div>

                <!-- Column 3 -->
                <div>
                    <h3 class="text-white font-semibold mb-5 text-sm uppercase tracking-wider">Platform</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="{{ route('platform.tracking') }}" class="hover:text-white transition">Shipment Tracking</a></li>
                        <li><a href="{{ route('platform.evidence') }}" class="hover:text-white transition">Video Evidence</a></li>
                        <li><a href="{{ route('platform.ndr') }}" class="hover:text-white transition">NDR Management</a></li>
                        <li><a href="{{ route('platform.rto') }}" class="hover:text-white transition">RTO Management</a></li>
                        <li><a href="{{ route('platform.cod') }}" class="hover:text-white transition">COD & Settlement</a></li>
                        <li><a href="{{ route('platform.awb') }}" class="hover:text-white transition">AWB & Labels</a></li>
                    </ul>
                </div>

                <!-- Column 4 -->
                <div>
                    <h3 class="text-white font-semibold mb-5 text-sm uppercase tracking-wider">Developers</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="{{ route('developers') }}" class="hover:text-white transition">API Overview</a></li>
                        <li><a href="{{ route('docs') }}" class="hover:text-white transition">API Documentation</a></li>
                        <li><a href="{{ route('docs') }}#webhooks" class="hover:text-white transition">Webhooks</a></li>
                        <li><a href="{{ route('docs') }}#sandbox" class="hover:text-white transition">Sandbox</a></li>
                    </ul>
                </div>

                <!-- Column 5 -->
                <div>
                    <h3 class="text-white font-semibold mb-5 text-sm uppercase tracking-wider">Company</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="{{ route('about') }}" class="hover:text-white transition">About Us</a></li>
                        <li><a href="{{ route('franchise') }}" class="hover:text-white transition">Franchise</a></li>
                        <li><a href="{{ route('partners') }}" class="hover:text-white transition">Partners</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:text-white transition">FAQ</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-16 pt-8 flex flex-col md:flex-row justify-between items-center text-sm gap-4">
                <div class="text-center md:text-left">
                    <p>&copy; {{ date('Y') }} OneStall Cargo &mdash; A Brand of <span class="text-gray-300 font-semibold">Anyurva Ayurveda Private Limited</span></p>
                    <p class="text-[11px] text-gray-600 mt-1">CIN: U21003UP2024PTC205462 &nbsp;|&nbsp; Lucknow, U.P. &nbsp;|&nbsp; All rights reserved.</p>
                </div>
                <div class="flex space-x-6 mt-2 md:mt-0">
                    <a href="/privacy" class="hover:text-white transition">Privacy Policy</a>
                    <a href="/terms" class="hover:text-white transition">Terms of Service</a>
                    <a href="/cookies" class="hover:text-white transition">Cookies</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>






