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
    <title>@yield('title', 'Seller Dashboard - OneStall Cargo')</title>
    <!-- Plus Jakarta Sans / Inter fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f5f9; color: #1e293b; }
        
        /* Clean Sidebar Item Base */
        .sidebar-item { 
            display: flex; 
            align-items: center; 
            height: 42px;
            padding: 0 14px;
            border-radius: 10px; 
            color: #64748b; 
            font-size: 13px; 
            font-weight: 600; 
            transition: all 0.2s ease; 
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 3px;
        }
        
        .sidebar-item:hover { 
            background: #f1f5f9; 
            color: #0f172a; 
        }
        
        .sidebar-item.active { 
            background: #eef2ff; 
            color: #4338ca; 
            font-weight: 700;
        }

        .icon-box {
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .topbar-input { background: transparent; border: none; outline: none; width: 100%; font-size: 13px; color: #475569; }
        .topbar-input::placeholder { color: #94a3b8; font-weight: 500; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="h-screen flex overflow-hidden bg-[#f3f5f9]" x-data="{ sidebarHover: false, isPinned: false, mobileSidebarOpen: false }">

    <!-- Seamless Transparent Page Loader (Logo centered inside spinning ring) -->
    <div id="global-loader" class="fixed inset-0 z-[9999] bg-white/85 backdrop-blur-sm flex flex-col items-center justify-center transition-all duration-300">
        <div class="flex flex-col items-center">
            
            <!-- Circular Container with Spinning Ring around Logo -->
            <div class="relative w-24 h-24 flex items-center justify-center">
                <!-- Static background ring -->
                <div class="absolute inset-0 rounded-full border-4 border-indigo-100"></div>
                
                <!-- Active spinning ring around logo -->
                <div class="absolute inset-0 rounded-full border-4 border-[#4338ca] border-t-transparent border-r-transparent animate-spin"></div>
                
                <!-- Center Circular Logo -->
                <div class="w-16 h-16 rounded-full bg-white p-2.5 flex items-center justify-center shadow-sm overflow-hidden z-10 animate-pulse">
                    <img src="{{ asset('images/logo.jpg') }}" alt="OneStall Cargo" class="w-full h-full object-contain">
                </div>
            </div>

            <p class="text-[11px] font-bold text-gray-500 tracking-[0.25em] uppercase mt-4">Loading...</p>
        </div>
    </div>
    
    <!-- Mobile Overlay -->
    <div x-show="mobileSidebarOpen" x-transition.opacity class="fixed inset-0 bg-gray-900/40 z-40 md:hidden" style="display: none;" @click="mobileSidebarOpen = false"></div>

    <!-- Left Sidebar -->
    <aside 
        @mouseenter="sidebarHover = true" 
        @mouseleave="sidebarHover = false"
        :class="{
            'w-64 shadow-2xl z-50 translate-x-0 fixed inset-y-0 left-0 md:relative': (sidebarHover || isPinned || mobileSidebarOpen),
            'w-64 -translate-x-full fixed inset-y-0 left-0 md:relative md:translate-x-0 md:w-20 z-30': !(sidebarHover || isPinned || mobileSidebarOpen)
        }"
        class="bg-white border-r border-gray-200 flex flex-col py-5 shrink-0 transition-all duration-300 ease-in-out"
        style="height: 100vh;">

        <!-- Pin / Lock Sidebar Toggle Button -->
        <button 
            @click="isPinned = !isPinned" 
            class="absolute -right-3 top-6 md:flex hidden w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-500 hover:text-[#4338ca] shadow-md z-50 transition-all duration-300"
            :class="isPinned ? 'rotate-180 bg-[#eef2ff] text-[#4338ca] border-[#c7d2fe]' : ''"
            :title="isPinned ? 'Unpin Sidebar' : 'Pin Sidebar Open'">
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
        </button>

        <!-- Logo Section -->
        <div class="px-4 mb-6 flex items-center h-10 transition-all overflow-hidden" :class="(sidebarHover || isPinned || mobileSidebarOpen) ? 'justify-start' : 'justify-center'">
            <a href="{{ route('seller.dashboard') }}" class="flex items-center hover:scale-105 transition-transform">
                <img src="{{ asset('images/logo.jpg') }}" alt="OneStall Cargo" class="object-contain rounded-lg transition-all duration-300" :class="(sidebarHover || isPinned || mobileSidebarOpen) ? 'h-10 max-w-[140px]' : 'h-8 w-8'">
            </a>
        </div>
        
        <!-- Navigation List -->
        <nav class="flex flex-col flex-1 w-full px-3 overflow-y-auto overflow-x-hidden">
            
            <a href="{{ route('seller.dashboard') }}" class="sidebar-item {{ request()->routeIs('seller.dashboard') ? 'active' : '' }}" title="Dashboard">
                <div class="icon-box">
                    <i class="fa-solid fa-border-all text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Dashboard</span>
            </a>

            <a href="{{ route('seller.shipments.index') }}" class="sidebar-item {{ request()->routeIs('seller.shipments.*') ? 'active' : '' }}" title="Orders">
                <div class="icon-box relative">
                    <i class="fa-solid fa-box text-base"></i>
                    @php $newCount = \App\Models\Shipment::where('user_id', Auth::id())->whereIn('status', ['new', 'New', 'Manifested', 'manifested', 'Booked', 'booked', 'pending', 'new order'])->count(); @endphp
                    @if($newCount > 0)
                        <span class="absolute top-0 right-0 w-2 h-2 rounded-full bg-[#4338ca]"></span>
                    @endif
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap flex-1 flex justify-between items-center">
                    <span>Orders</span>
                    @if($newCount > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold bg-[#eef2ff] text-[#4338ca] rounded-full">{{ $newCount }}</span>
                    @endif
                </span>
            </a>

            <a href="{{ route('seller.book') }}" class="sidebar-item {{ request()->routeIs('seller.book') ? 'active' : '' }}" title="Add Order">
                <div class="icon-box">
                    <i class="fa-solid fa-square-plus text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Add Order</span>
            </a>

            <a href="{{ route('seller.ndr') }}" class="sidebar-item {{ request()->routeIs('seller.ndr') ? 'active' : '' }}" title="NDR Management">
                <div class="icon-box">
                    <i class="fa-solid fa-arrow-rotate-left text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">NDR Management</span>
            </a>

            <a href="{{ route('seller.weight') }}" class="sidebar-item {{ request()->routeIs('seller.weight') ? 'active' : '' }}" title="Weight Reconciliation">
                <div class="icon-box">
                    <i class="fa-solid fa-scale-balanced text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Weight Reconciliation</span>
            </a>

            <a href="{{ route('seller.weight.freeze') }}" class="sidebar-item {{ request()->routeIs('seller.weight.freeze') ? 'active' : '' }}" title="Weight Freeze">
                <div class="icon-box">
                    <i class="fa-solid fa-snowflake text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Weight Freeze</span>
            </a>

            <a href="{{ route('seller.tools') }}" class="sidebar-item {{ request()->routeIs('seller.tools') ? 'active' : '' }}" title="Tools">
                <div class="icon-box">
                    <i class="fa-solid fa-wrench text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Tools & Rate Calc</span>
            </a>

            <a href="{{ route('seller.reports.index') }}" class="sidebar-item {{ request()->routeIs('seller.reports*') ? 'active' : '' }}" title="Monthly Reports & GST Bills">
                <div class="icon-box">
                    <i class="fa-solid fa-file-invoice text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Reports & GST Bills</span>
            </a>

            <a href="{{ route('seller.wallet') }}" class="sidebar-item {{ request()->routeIs('seller.wallet') ? 'active' : '' }}" title="Billing">
                <div class="icon-box">
                    <i class="fa-solid fa-wallet text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Billing & Wallet</span>
            </a>

                        <a href="{{ route('seller.early-cod') }}" class="sidebar-item {{ request()->routeIs('seller.early-cod') ? 'active' : '' }}" title="Early COD">
                <div class="icon-box">
                    <i class="fa-solid fa-bolt text-yellow-500 text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#5d16c5] to-[#0ea5e9]">Early COD</span>
            </a>

            <!-- Channels / Integrations -->
            <a href="{{ route('seller.integrations') }}" class="sidebar-item {{ request()->routeIs('seller.integrations*') ? 'active' : '' }}" title="Store Integrations">
                <div class="icon-box">
                    <i class="fa-solid fa-plug text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Integrations</span>
            </a>

            <!-- API Integration Tab -->
            <a href="{{ route('seller.api-keys') }}" class="sidebar-item {{ request()->routeIs('seller.api-keys') ? 'active' : '' }}" title="API Integration">
                <div class="icon-box">
                    <i class="fa-solid fa-code text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">API Integration</span>
            </a>

            <a href="{{ route('seller.settings') }}" class="sidebar-item {{ request()->routeIs('seller.settings') ? 'active' : '' }}" title="Settings">
                <div class="icon-box">
                    <i class="fa-solid fa-gear text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Settings</span>
            </a>

        </nav>
        
        <!-- Bottom Help Section -->
        <div class="px-3 mt-auto pt-3 border-t border-gray-100">
            <a href="{{ route('contact') }}" target="_blank" class="sidebar-item" title="Support">
                <div class="icon-box">
                    <i class="fa-regular fa-circle-question text-base"></i>
                </div>
                <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Support & Help</span>
            </a>
        </div>
    </aside>

    <!-- Main Container -->
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        
        <!-- Premium Top Navbar -->
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 shrink-0 z-20">
            
            <!-- Mobile Menu Toggle & Brand (Mobile only) -->
            <div class="flex items-center gap-3 md:hidden">
                <button @click="mobileSidebarOpen = true" class="text-gray-500 hover:text-[#4338ca] focus:outline-none transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="h-8 object-contain rounded">
            </div>

            <!-- Left: Global Search (Hidden on Mobile) -->
            <div class="hidden md:flex items-center w-full max-w-md ml-4">
                <div class="flex items-center bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 w-full focus-within:bg-white focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-50 transition-all">
                    <i class="fa-solid fa-magnifying-glass text-gray-400 mr-2 text-sm"></i>
                    <input type="text" class="topbar-input bg-transparent w-full border-none focus:ring-0 text-[13px] text-gray-800 placeholder-gray-400 outline-none" placeholder="Search by AWB / Order ID...">
                    <div class="ml-2 flex items-center justify-center bg-white text-gray-500 rounded px-1.5 py-0.5 text-[10px] font-bold border border-gray-200 shadow-sm whitespace-nowrap">Ctrl K</div>
                </div>
            </div>

            <!-- Right: Action Pills, Wallet, Activity, Profile -->
            <div class="flex items-center gap-2.5 sm:gap-3.5 ml-auto">
                <a href="{{ route('seller.wallet') }}" class="hidden sm:flex items-center bg-[#5438dc] hover:bg-[#472ecc] text-white px-3.5 py-1.5 rounded-full text-xs font-bold transition shadow-sm">
                    <i class="fa-solid fa-bolt mr-1.5 text-yellow-300"></i> Recharge
                </a>
                <a href="{{ route('seller.ndr') }}" class="hidden sm:flex items-center bg-[#ea3d3d] hover:bg-[#d83535] text-white px-3.5 py-1.5 rounded-full text-xs font-bold transition shadow-sm">
                    <i class="fa-solid fa-circle-exclamation mr-1.5"></i> Escalation
                </a>
                
                <!-- Wallet Badge -->
                <div class="flex items-center bg-[#eef2ff] border border-[#c7d2fe] rounded-full px-3.5 py-1.5 text-xs font-bold text-[#4338ca]">
                    <i class="fa-solid fa-wallet mr-1.5"></i> &#8377; {{ number_format(Auth::user()->wallet_balance ?? 0, 2) }}
                    <a href="{{ route('seller.wallet') }}" class="ml-2 text-[#4338ca] hover:text-[#312a91] transition"><i class="fa-solid fa-rotate-right"></i></a>
                </div>
                
                <!-- Notification Bell -->
                <div class="relative" x-data="{ openActivity: false }">
                    <button @click="openActivity = !openActivity" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center text-sm relative transition">
                        <i class="fa-regular fa-bell"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-indigo-500 rounded-full"></span>
                    </button>
                </div>

                <!-- User Profile Dropdown -->
                <div class="relative" x-data="{ openProfile: false }">
                    <div @click="openProfile = !openProfile" class="flex items-center gap-2 ml-1 pl-2 border-l border-gray-200 cursor-pointer select-none">
                        <div class="w-8 h-8 rounded-full bg-[#4338ca] text-white font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr(Auth::user()->company_name ?? Auth::user()->name ?? 'S', 0, 1)) }}
                        </div>
                        <span class="text-xs font-bold text-gray-700 hidden lg:block">{{ Auth::user()->company_name ?? Auth::user()->name ?? 'Company' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 hidden lg:block"></i>
                    </div>

                    <div x-show="openProfile" @click.away="openProfile = false" class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-2xl shadow-xl py-2 z-50 text-left" style="display: none;" x-transition>
                        
                        <!-- Header inside dropdown -->
                        <div class="px-4 py-3 border-b border-gray-100 mb-2">
                            <p class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-[10px] text-gray-500 truncate mt-0.5">{{ Auth::user()->email ?? '' }}</p>
                        </div>

                        <a href="{{ route('seller.settings') }}?view=company" class="flex items-center px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-[#4338ca] transition">
                            <i class="fa-solid fa-user-tie w-5 text-gray-400"></i> Manage Profile
                        </a>
                        <a href="{{ route('seller.settings') }}" class="flex items-center px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-[#4338ca] transition mb-2">
                            <i class="fa-solid fa-gear w-5 text-gray-400"></i> Global Settings
                        </a>
                        
                        <form action="{{ route('logout') }}" method="POST" class="border-t border-gray-100 pt-2">
                            @csrf
                            <button class="w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 hover:text-red-700 transition outline-none flex items-center">
                                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-red-400"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto relative">
            <div class="p-6 md:p-8 max-w-[1600px] mx-auto">
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-xl font-semibold bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center shadow-xs">
                        <i class="fa-solid fa-circle-check mr-2 text-emerald-600"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-6 p-4 rounded-xl font-semibold bg-rose-50 border border-rose-200 text-rose-800 flex items-center shadow-xs">
                        <i class="fa-solid fa-circle-exclamation mr-2 text-rose-600"></i> {{ session('error') }}
                    </div>
                @endif
                
                @yield('content')
            </div>
        </main>
    </div>
    <script>
        // Hide loader when page finishes loading
        window.addEventListener('load', function () {
            const loader = document.getElementById('global-loader');
            if(loader) {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 250);
            }
        });

        // Show loader when leaving the page (clicking a link or submitting a form)
        window.addEventListener('beforeunload', function () {
            const loader = document.getElementById('global-loader');
            if(loader) {
                loader.style.display = 'flex';
                loader.style.opacity = '1';
            }
        });
    </script>
    @if(session('print_awb'))
    <script>
        window.open('{{ route('seller.label', session('print_awb')) }}', '_blank');
    </script>
    @endif
</body>
</html>

