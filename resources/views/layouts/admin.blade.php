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
    <title>@yield('title', 'Admin Console - OneStall Cargo')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --gold: #FFD700; --gold-deep: #D4AF37; --bg-body: #f8fafc;
            --text-main: #0f172a; --theme-bg: #1e293b; --theme-text: #94a3b8;
            --theme-active: #FFD700; --theme-active-text: #0f172a;
        }
        body { font-family: 'Nunito', sans-serif; background-color: var(--bg-body); color: var(--text-main); }
        .sidebar-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 12px; color: var(--theme-text); font-size: 13px; font-weight: 700; transition: all 0.2s ease; }
        .sidebar-item:hover { background-color: rgba(255, 255, 255, 0.05); color: white; }
        .sidebar-item.active { background-color: rgba(255, 215, 0, 0.1); color: var(--theme-active); }
        .sidebar-item.active i { color: var(--theme-active); }
    </style>
</head>
<body class="h-screen min-h-[100dvh] flex overflow-hidden bg-[#f3f5f9]" x-data="{ sidebarHover: false, isPinned: false, mobileSidebarOpen: false }">
    
    <!-- Desktop sidebar -->
<!-- Mobile overlay -->
<div x-show="mobileSidebarOpen" x-transition.opacity class="fixed inset-0 bg-gray-900/40 z-40 md:hidden" style="display: none;" @click="mobileSidebarOpen = false"></div>

<!-- Sidebar -->
<div @mouseenter="sidebarHover = true" 
     @mouseleave="sidebarHover = false"
     :class="{
         'w-64 shadow-2xl z-50 translate-x-0 fixed inset-y-0 left-0 md:relative': (sidebarHover || isPinned || mobileSidebarOpen),
         'w-64 -translate-x-full fixed inset-y-0 left-0 md:relative md:translate-x-0 md:w-20 z-30': !(sidebarHover || isPinned || mobileSidebarOpen)
     }"
     class="flex flex-col shrink-0 transition-all duration-300 ease-in-out h-screen" style="background-color: var(--theme-bg);">
    <button 
        @click="isPinned = !isPinned" 
        class="absolute -right-3 top-6 md:flex hidden w-6 h-6 bg-white border border-gray-200 rounded-full items-center justify-center text-gray-500 hover:text-[#4338ca] shadow-md z-50 transition-all duration-300"
        :class="isPinned ? 'rotate-180 bg-[#eef2ff] text-[#4338ca] border-[#c7d2fe]' : ''"
        :title="isPinned ? 'Unpin Sidebar' : 'Pin Sidebar Open'">
        <i class="fa-solid fa-chevron-left text-[10px]"></i>
    </button>
<div class="flex flex-col flex-1 overflow-y-auto overflow-x-hidden scrollbar-hide">
            <div class="flex items-center px-4 mb-6 mt-5 text-white gap-2.5">
                <img src="{{ asset('images/logo.jpg') }}" alt="OneStall Cargo" class="h-10 object-contain rounded bg-white p-1" style="max-width: 150px;">
                <div class="flex flex-col"><span class="text-[10px] text-[var(--gold)] font-bold uppercase tracking-wider">Admin</span></div>
            </div>

            <nav class="flex-1 space-y-1 px-2 pb-4">
                <div x-show="sidebarHover || isPinned || mobileSidebarOpen" class="px-3 mt-4 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">Main Navigation</div><div x-show="!(sidebarHover || isPinned || mobileSidebarOpen)" class="mt-4 mb-2 border-t border-white/10 mx-4"></div>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="fa-solid fa-chart-pie  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Overview</span></a>
                <a href="{{ route('admin.map') }}" class="sidebar-item {{ request()->routeIs('admin.map') ? 'active' : '' }}"><i class="fa-solid fa-map-location-dot  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Live Fleet Map</span></a>

                <div x-show="sidebarHover || isPinned || mobileSidebarOpen" class="px-3 mt-6 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">Operations</div><div x-show="!(sidebarHover || isPinned || mobileSidebarOpen)" class="mt-6 mb-2 border-t border-white/10 mx-4"></div>
                <a href="{{ route('admin.shipments.index') }}" class="sidebar-item {{ request()->routeIs('admin.shipments.*') ? 'active' : '' }}"><i class="fa-solid fa-box  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Shipments</span></a>
                <a href="{{ route('admin.pickups.index') }}" class="sidebar-item {{ request()->routeIs('admin.pickups.*') ? 'active' : '' }}"><i class="fa-solid fa-truck-fast  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Pickups</span></a>
                <a href="{{ route('admin.ndr.index') }}" class="sidebar-item {{ request()->routeIs('admin.ndr.*') ? 'active' : '' }}"><i class="fa-solid fa-triangle-exclamation  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">NDR & RTO</span></a>
                        <a href="{{ route('admin.weight') }}" class="sidebar-item {{ request()->routeIs('admin.weight') ? 'active' : '' }}"><i class="fa-solid fa-scale-balanced  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Weight Reconciliation</span></a>
                        <a href="{{ route('admin.weight.freeze') }}" class="sidebar-item {{ request()->routeIs('admin.weight.freeze') ? 'active' : '' }}"><i class="fa-solid fa-snowflake  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Weight Freeze</span></a>
                <a href="{{ route('admin.evidence.index') }}" class="sidebar-item {{ request()->routeIs('admin.evidence.*') ? 'active' : '' }}"><i class="fa-solid fa-video  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Evidence DB</span></a>                        <a href="{{ route('admin.riders.index') }}" class="sidebar-item {{ request()->routeIs('admin.riders.*') ? 'active' : '' }}"><i class="fa-solid fa-motorcycle  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Fleet (Riders)</span></a>

                
                        <div x-show="sidebarHover || isPinned || mobileSidebarOpen" class="px-3 mt-6 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">Content Management</div><div x-show="!(sidebarHover || isPinned || mobileSidebarOpen)" class="mt-6 mb-2 border-t border-white/10 mx-4"></div>
                        <a href="{{ route('admin.banners.index') }}" class="sidebar-item {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}"><i class="fa-solid fa-image  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Promo Banners</span></a>
                        <a href="{{ route('admin.services.index') }}" class="sidebar-item {{ request()->routeIs('admin.services.*') ? 'active' : '' }}"><i class="fa-solid fa-list-check  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Services</span></a>
                        <a href="{{ route('admin.faqs.index') }}" class="sidebar-item {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}"><i class="fa-solid fa-circle-question  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">FAQs</span></a>
                        <a href="{{ route('admin.testimonials.index') }}" class="sidebar-item {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}"><i class="fa-solid fa-star  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Testimonials</span></a>

                        <div x-show="sidebarHover || isPinned || mobileSidebarOpen" class="px-3 mt-6 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">System</div><div x-show="!(sidebarHover || isPinned || mobileSidebarOpen)" class="mt-6 mb-2 border-t border-white/10 mx-4"></div>
                        <a href="{{ route('admin.settings.index') }}" class="sidebar-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><i class="fa-solid fa-gear  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Settings</span></a>

<div x-show="sidebarHover || isPinned || mobileSidebarOpen" class="px-3 mt-6 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">Network</div><div x-show="!(sidebarHover || isPinned || mobileSidebarOpen)" class="mt-6 mb-2 border-t border-white/10 mx-4"></div>
                <a href="{{ route('admin.hubs.index') }}" class="sidebar-item {{ request()->routeIs('admin.hubs.*') ? 'active' : '' }}"><i class="fa-solid fa-building  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Hubs / Franchise</span></a>
                <a href="{{ route('admin.pincodes.index') }}" class="sidebar-item {{ request()->routeIs('admin.pincodes.*') ? 'active' : '' }}"><i class="fa-solid fa-map-location-dot  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Pincode Mapping</span></a>
                <a href="{{ route('admin.couriers.index') }}" class="sidebar-item {{ request()->routeIs('admin.couriers.*') ? 'active' : '' }}"><i class="fa-solid fa-network-wired  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Couriers API</span></a>

                <div x-show="sidebarHover || isPinned || mobileSidebarOpen" class="px-3 mt-6 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">Business</div><div x-show="!(sidebarHover || isPinned || mobileSidebarOpen)" class="mt-6 mb-2 border-t border-white/10 mx-4"></div>
                <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.customers.*') ? 'bg-gray-800 text-white font-bold' : '' }}">
                    <i class="fa-solid fa-user-tag  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">B2C Customers</span>
                </a>
                <a href="{{ route('admin.sellers.index') }}" class="sidebar-item {{ request()->routeIs('admin.sellers.*') ? 'active' : '' }}"><i class="fa-solid fa-users  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Sellers Directory</span></a>
                <a href="{{ route('admin.kyc.index') }}" class="sidebar-item {{ request()->routeIs('admin.kyc.*') ? 'active' : '' }}"><i class="fa-solid fa-id-card  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">KYC Approvals</span></a>
                <a href="{{ route('admin.ratecards.index') }}" class="sidebar-item {{ request()->routeIs('admin.ratecards.*') ? 'active' : '' }}"><i class="fa-solid fa-indian-rupee-sign  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Rate Engine</span></a>
                <a href="{{ route('admin.billing.index') }}" class="sidebar-item {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}"><i class="fa-solid fa-file-invoice-dollar  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Billing & COD</span></a>

                <div x-show="sidebarHover || isPinned || mobileSidebarOpen" class="px-3 mt-6 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">System</div><div x-show="!(sidebarHover || isPinned || mobileSidebarOpen)" class="mt-6 mb-2 border-t border-white/10 mx-4"></div>
                <a href="{{ route('admin.reports.index') }}" class="sidebar-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"><i class="fa-solid fa-file-invoice  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Reports</span></a>
                        <a href="{{ route('admin.roles.index') }}" class="sidebar-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"><i class="fa-solid fa-user-shield  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">All Users & Roles</span></a>                        <a href="{{ route('admin.integrations') }}" class="sidebar-item {{ request()->routeIs('admin.integrations') ? 'active' : '' }}"><i class="fa-solid fa-plug  w-5 text-center text-lg"></i> <span x-show="sidebarHover || isPinned || mobileSidebarOpen" x-transition.opacity class="ml-3 whitespace-nowrap">Integrations</span></a>
            </nav>
            
            <div class="p-4 border-t border-white/10 shrink-0">
                <div class="flex items-center">
                    <div class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[var(--theme-active)] text-[var(--theme-active-text)] font-bold">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">{{ Auth::user()->name ?? 'Administrator' }}</p>
                        <form action="{{ route('logout') }}" method="POST" class="mt-1">@csrf <button type="submit" class="flex items-center gap-1.5 px-2.5 py-1 bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white rounded-md text-xs font-bold transition"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button></form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="flex-1 flex flex-col transition-all duration-300 h-full overflow-hidden relative">
        <div class="flex h-16 shrink-0 bg-white shadow-sm border-b border-gray-200">
            <button type="button" @click="mobileSidebarOpen = true" class="px-4 text-gray-500 md:hidden border-r border-gray-200"><i class="fa-solid fa-bars text-xl"></i></button>
            <div class="flex flex-1 justify-between px-4 items-center">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h2 class="text-sm sm:text-base font-extrabold text-gray-800">
                        <span class="hidden sm:inline">OneStall Cargo Command Center</span>
                        <span class="sm:hidden">OneStall Admin</span>
                    </h2>
                </div>

                <!-- Right: Admin Profile & Menu -->
                <div class="flex items-center gap-2 sm:gap-3" x-data="{ openAdminMenu: false }">
                    <!-- User Profile Dropdown -->
                    <div class="relative">
                        <button @click="openAdminMenu = !openAdminMenu" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 transition focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-slate-900 text-yellow-400 font-black text-xs flex items-center justify-center shadow-xs">
                                {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                            </div>
                            <span class="text-xs font-bold text-gray-700 hidden md:block">{{ Auth::user()->name ?? 'Admin' }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
                        </button>

                        <div x-show="openAdminMenu" @click.away="openAdminMenu = false" class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-2xl shadow-xl py-2 z-50 text-left" style="display: none;" x-transition>
                            <div class="px-4 py-2 border-b border-gray-100 mb-1">
                                <p class="text-xs font-extrabold text-gray-900 truncate">{{ Auth::user()->name ?? 'Administrator' }}</p>
                                <p class="text-[10px] text-gray-500 truncate">{{ Auth::user()->email ?? '' }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-100 text-yellow-800 text-[9px] font-black rounded-full uppercase">Super Admin</span>
                            </div>

                            <a href="{{ route('admin.settings.index') }}" class="flex items-center px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-emerald-700 transition">
                                <i class="fa-solid fa-gear w-5 text-gray-400"></i> System Settings
                            </a>
                            <a href="{{ route('admin.roles.index') }}" class="flex items-center px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-emerald-700 transition">
                                <i class="fa-solid fa-user-shield w-5 text-gray-400"></i> Users & Roles
                            </a>
                            
                            <form action="{{ route('logout') }}" method="POST" class="border-t border-gray-100 pt-1 mt-1">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 flex items-center transition">
                                    <i class="fa-solid fa-arrow-right-from-bracket w-5 text-red-500"></i> Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <main class="flex-1 overflow-y-auto p-3 sm:p-6 md:p-8 pb-32 sm:pb-28" style="-webkit-overflow-scrolling: touch;">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl" style="background-color: #f0fdf4; border: 1px solid #16a34a; color: #16a34a;"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
            @endif
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>





