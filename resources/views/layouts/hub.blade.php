<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Franchise Hub - OneStall Cargo</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root { --gold: #FFD700; --gold-deep: #FDB931; }
    </style>
</head>
<body class="bg-gray-50 font-[Nunito] flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    
    <!-- Mobile Sidebar overlay -->
    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 lg:hidden" style="display: none;"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-30 w-64 bg-[#1e293b] text-white flex flex-col h-full shrink-0 shadow-2xl transition-transform duration-300 lg:static lg:translate-x-0">
        <div class="p-6 flex items-center justify-between gap-3 border-b border-gray-700/50">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="h-10 rounded bg-white p-1">
                <div>
                    <div class="font-extrabold text-sm leading-tight text-[var(--gold)]">HUB PORTAL</div>
                </div>
            </div>
            <!-- Close button for mobile -->
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                <i class="fa-solid fa-xmark fa-lg"></i>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
            <div class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3 ml-2 mt-4">Core</div>
            
            <a href="{{ route('hub.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.dashboard') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-house w-5"></i> Dashboard
            </a>
            <a href="{{ route('hub.shipments') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.shipments') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-box w-5"></i> Shipments
            </a>
            <a href="{{ route('hub.shipments.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.shipments.create') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-pen-to-square w-5"></i> New Booking
            </a>
            <a href="{{ route('hub.wallet.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.wallet.*') ? 'bg-gray-800 text-white' : '' }}">
                <span class="flex items-center gap-3">
                    <i class="fa-solid fa-wallet w-5 text-amber-400"></i> Wallet & Recharge
                </span>
                <span class="text-[11px] font-black text-[var(--gold)]">₹{{ number_format(Auth::user()->wallet_balance ?? 0, 0) }}</span>
            </a>

            <div class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3 ml-2 mt-6">Operations</div>

            <a href="{{ route('hub.assignments.pickups') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.assignments.pickups') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-truck-pickup w-5"></i> Pickup
            </a>
            <a href="{{ route('hub.assignments.deliveries') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.assignments.deliveries') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-motorcycle w-5"></i> Delivery
            </a>
            <a href="{{ route('hub.ndr.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.ndr.index') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-rotate-left w-5"></i> NDR / RTO
            </a>

            <div class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3 ml-2 mt-6">Network</div>
            
            <a href="{{ route('hub.fleet.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.fleet.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-users-gear w-5"></i> Staff / Delivery Partners
            </a>
            <a href="{{ route('hub.bagging.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition">
                <i class="fa-solid fa-warehouse w-5"></i> Warehouse
            </a>

            <div class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3 ml-2 mt-6">Finance & Network</div>

            <a href="{{ route('hub.wallet.index') ?? '#' }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.wallet.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-wallet w-5"></i> Earnings & COD
            </a>
            <a href="{{ route('hub.customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition">
                <i class="fa-solid fa-users w-5"></i> Customers
            </a>
            <a href="{{ route('hub.sellers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition">
                <i class="fa-solid fa-store w-5"></i> Sellers
            </a>
            <a href="{{ route('hub.service_areas.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition">
                <i class="fa-solid fa-map-location-dot w-5"></i> Service Areas
            </a>

            <div class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-3 ml-2 mt-6">System</div>

            <a href="{{ route('hub.reports.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white font-bold transition {{ request()->routeIs('hub.reports.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fa-solid fa-chart-line w-5"></i> Reports
            </a>
            <a href="{{ route('hub.whatsapp.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('hub.whatsapp.*') ? 'bg-gray-800 text-green-400' : 'text-green-500' }} hover:bg-gray-800 hover:text-green-400 font-bold transition">
                <i class="fa-brands fa-whatsapp w-5 text-lg"></i> WhatsApp
            </a>
            <a href="{{ route('hub.support.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('hub.support.*') ? 'bg-gray-800 text-white' : 'text-gray-400' }} hover:bg-gray-800 hover:text-white font-bold transition">
                <i class="fa-solid fa-headset w-5"></i> Support
            </a>
            <a href="{{ route('hub.notifications.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('hub.notifications.*') ? 'bg-gray-800 text-white' : 'text-gray-400' }} hover:bg-gray-800 hover:text-white font-bold transition">
                <i class="fa-solid fa-bell w-5"></i> Notifications
            </a>
        </div>
        
        <div class="px-4 pb-2 pt-2 border-t border-gray-700/50">
            <a href="{{ route('hub.profile') }}?view=kyc" class="flex items-center justify-between px-3 py-2.5 rounded-xl {{ (request()->routeIs('hub.profile*') && request('view') === 'kyc') ? 'bg-gray-800 text-white' : 'text-gray-400' }} hover:bg-gray-800 hover:text-white font-bold transition">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-id-card w-5"></i> KYC Verification
                </div>
                @php $hubUserKyc = Auth::user()->kyc; @endphp
                @if($hubUserKyc && $hubUserKyc->status === 'approved')
                    <span class="px-1.5 py-0.5 bg-emerald-500/20 text-emerald-400 text-[9px] font-black uppercase rounded">Active</span>
                @elseif($hubUserKyc && $hubUserKyc->status === 'pending')
                    <span class="px-1.5 py-0.5 bg-amber-500/20 text-amber-400 text-[9px] font-black uppercase rounded">Review</span>
                @else
                    <span class="px-1.5 py-0.5 bg-red-500/20 text-red-400 text-[9px] font-black uppercase rounded">Pending</span>
                @endif
            </a>
            <a href="{{ route('hub.integrations') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('hub.integrations*') ? 'bg-gray-800 text-white' : 'text-gray-400' }} hover:bg-gray-800 hover:text-white font-bold transition">
                <i class="fa-solid fa-plug w-5"></i> Integrations
            </a>
            <a href="{{ route('hub.profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ (request()->routeIs('hub.profile*') && request('view') !== 'kyc') ? 'bg-gray-800 text-white' : 'text-gray-400' }} hover:bg-gray-800 hover:text-white font-bold transition">
                <i class="fa-solid fa-gear w-5"></i> Settings
            </a>
        </div>
        
        <div class="p-4 border-t border-gray-700/50">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white rounded-xl font-bold transition">
                    <i class="fa-solid fa-power-off"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-full overflow-hidden w-full lg:w-auto">
        
        <!-- Topbar -->
        <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-8 shrink-0 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none">
                    <i class="fa-solid fa-bars fa-xl"></i>
                </button>
                <h2 class="text-lg lg:text-xl font-extrabold text-gray-900 truncate max-w-[150px] lg:max-w-xs">{{ Auth::user()->name }}</h2>
                <span class="hidden sm:inline-block px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold uppercase rounded-full">Franchise Partner</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('hub.wallet.index') }}" class="px-3.5 py-1.5 bg-gray-900 hover:bg-black text-white rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm transition">
                    <i class="fa-solid fa-wallet text-[var(--gold)]"></i>
                    <span>₹{{ number_format(Auth::user()->wallet_balance ?? 0, 2) }}</span>
                    <span class="px-1.5 py-0.5 bg-amber-400/20 text-amber-300 text-[10px] rounded uppercase font-black">+ Recharge</span>
                </a>
                <div class="text-xs lg:text-sm font-bold text-gray-500 hidden md:flex items-center gap-1.5 pl-2 border-l border-gray-200">
                    <i class="fa-solid fa-location-dot"></i> <span class="hidden sm:inline">Your Hub Network</span>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="flex-1 overflow-y-auto bg-gray-50 p-4 lg:p-8">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl font-semibold bg-emerald-50 border border-emerald-300 text-emerald-800 flex items-center shadow-xs">
                    <i class="fa-solid fa-circle-check mr-2 text-emerald-600 text-lg"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl font-bold bg-rose-50 border-2 border-rose-400 text-rose-900 flex items-start gap-3 shadow-md">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-xl mt-0.5 shrink-0"></i>
                    <div>
                        <div class="font-extrabold text-sm text-rose-800">Error Notice:</div>
                        <div class="text-xs text-rose-700 mt-0.5">{{ session('error') }}</div>
                    </div>
                </div>
            @endif
            @yield('content')
        </div>
        
    </main>
</body>
</html>

