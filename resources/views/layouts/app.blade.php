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
    <title>@yield('title', 'OneStall Cargo - Premier Logistics & Courier Company')</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- AlpineJS for mobile menu -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Nunito', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

    <!-- Navbar -->
    <nav class="bg-[#1e293b] text-white shadow-xl sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center gap-3">
                        <img src="{{ asset('images/logo.jpg') }}" alt="OneStall Cargo" class="h-10 object-contain rounded bg-white p-1" style="max-width: 150px;">
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" class="text-sm font-bold text-gray-300 hover:text-white transition">Home</a>
                    <a href="{{ route('services') }}" class="text-sm font-bold text-gray-300 hover:text-white transition">Services</a>
                    <a href="{{ route('pricing') }}" class="text-sm font-bold text-gray-300 hover:text-white transition">Pricing</a>
                    <a href="{{ route('franchise') }}" class="text-sm font-bold text-gray-300 hover:text-white transition">Franchise</a>
                    <a href="{{ route('api-docs') }}" class="text-sm font-bold text-gray-300 hover:text-white transition">API Docs</a>
                    
                    <a href="{{ route('track') }}" class="text-sm font-bold px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 hover:bg-gray-700 transition">
                        <i class="fa-solid fa-location-crosshairs text-[#FFD700] mr-1"></i> Track
                    </a>
                    
                    <div class="border-l border-gray-700 h-6 mx-2"></div>

                    @auth
                        <a href="{{ route('login') }}" class="text-sm font-extrabold px-6 py-2.5 rounded-xl bg-[#FFD700] text-gray-900 hover:bg-[#E5C100] transition shadow-md">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-bold text-gray-300 hover:text-white transition">Log In</a>
                        <a href="{{ route('register') }}" class="text-sm font-extrabold px-6 py-2.5 rounded-xl bg-[#FFD700] text-gray-900 hover:bg-[#E5C100] transition shadow-md">Sign Up Free</a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-300 hover:text-white">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" class="md:hidden bg-gray-800 border-t border-gray-700">
            <div class="px-4 pt-2 pb-6 space-y-2">
                <a href="/" class="block px-3 py-2 text-base font-bold text-gray-300 hover:text-white">Home</a>
                <a href="{{ route('services') }}" class="block px-3 py-2 text-base font-bold text-gray-300 hover:text-white">Services</a>
                <a href="{{ route('pricing') }}" class="block px-3 py-2 text-base font-bold text-gray-300 hover:text-white">Pricing</a>
                <a href="{{ route('franchise') }}" class="block px-3 py-2 text-base font-bold text-gray-300 hover:text-white">Franchise</a>
                <a href="{{ route('api-docs') }}" class="block px-3 py-2 text-base font-bold text-gray-300 hover:text-white">API</a>
                <a href="{{ route('track') }}" class="block px-3 py-2 text-base font-bold text-[#FFD700]">Track Parcel</a>
                <div class="border-t border-gray-700 my-2 pt-2"></div>
                @auth
                    <a href="{{ route('login') }}" class="block px-3 py-2 text-base font-bold text-[#FFD700]">My Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2 text-base font-bold text-gray-300 hover:text-white">Log In</a>
                    <a href="{{ route('register') }}" class="block px-3 py-2 text-base font-bold text-[#FFD700]">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-[#0f172a] text-gray-400 py-12 border-t border-gray-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Branding -->
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="{{ asset('images/logo.jpg') }}" alt="OneStall Cargo" class="h-10 object-contain rounded bg-white p-1" style="max-width: 150px;">
                    </div>
                    <p class="text-sm mb-6 leading-relaxed">Your ultimate logistics partner. Tech-enabled courier and cargo company providing express B2C delivery, B2B freight, nationwide franchise hubs, and business shipping APIs.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-500 hover:text-[#FFD700] transition"><i class="fa-brands fa-linkedin text-xl"></i></a>
                        <a href="#" class="text-gray-500 hover:text-[#FFD700] transition"><i class="fa-brands fa-twitter text-xl"></i></a>
                        <a href="#" class="text-gray-500 hover:text-[#FFD700] transition"><i class="fa-brands fa-facebook text-xl"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-white font-bold mb-4 uppercase tracking-wider text-xs">Solutions</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('services') }}" class="hover:text-[#FFD700] transition">Domestic Shipping (B2C)</a></li>
                        <li><a href="{{ route('services') }}" class="hover:text-[#FFD700] transition">Cargo & Freight (B2B)</a></li>
                        <li><a href="{{ route('services') }}" class="hover:text-[#FFD700] transition">International Cross-border</a></li>
                        <li><a href="{{ route('api-docs') }}" class="hover:text-[#FFD700] transition">E-commerce API Integration</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="text-white font-bold mb-4 uppercase tracking-wider text-xs">Company</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-[#FFD700] transition">About Us</a></li>
                        <li><a href="{{ route('franchise') }}" class="hover:text-[#FFD700] transition">Become a Franchise (Hub)</a></li>
                        <li><a href="{{ route('pricing') }}" class="hover:text-[#FFD700] transition">Pricing & Rates</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-[#FFD700] transition">Contact Support</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-white font-bold mb-4 uppercase tracking-wider text-xs">Get In Touch</h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start"><i class="fa-solid fa-location-dot mt-1 mr-3 text-[#FFD700]"></i> {{ setting('site_address', '123 Logistics Park, Mumbai, India 400001') }}</li>
                        <li class="flex items-center"><i class="fa-solid fa-envelope mr-3 text-[#FFD700]"></i> {{ setting('site_email', 'support@onestallcargo.com') }}</li>
                        <li class="flex items-center"><i class="fa-solid fa-phone mr-3 text-[#FFD700]"></i> {{ setting('site_phone', '1800-123-4567') }}</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center text-xs">
                <p>&copy; 2026 OneStall Cargo. All rights reserved.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" class="hover:text-white transition">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>

