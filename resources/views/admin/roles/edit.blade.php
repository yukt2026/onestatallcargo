@extends('layouts.admin')
@section('title', 'Manage User Profile - OneStall Cargo')
@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    
    @if(session('error'))
        <div class="p-4 rounded-xl font-bold bg-red-50 border border-red-500 text-red-700"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="p-4 rounded-xl font-bold bg-green-50 border border-green-500 text-green-700"><i class="fa-solid fa-check-circle"></i> {{ session('success') }}</div>
    @endif

    <!-- Top Profile Banner -->
    <div class="relative bg-gradient-to-r from-[#064e3b] to-[#10b981] rounded-[2rem] p-8 overflow-hidden shadow-lg border border-emerald-900/20">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center gap-6">
                <div class="w-24 h-24 bg-white rounded-full p-1 shadow-md relative">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=e2e8f0&color=0f172a&size=200" class="w-full h-full rounded-full object-cover" alt="Avatar">
                    <div class="absolute bottom-0 right-0 w-7 h-7 bg-white rounded-full p-1 shadow">
                        <div class="w-full h-full bg-emerald-500 rounded-full flex items-center justify-center text-white text-[10px]"><i class="fa-solid fa-camera"></i></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-3xl font-black text-white tracking-tight">{{ $user->name }}</h1>
                        <span class="bg-emerald-400/20 text-emerald-100 border border-emerald-400/30 text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-widest">{{ str_replace('_', ' ', $user->role) }}</span>
                    </div>
                    <p class="text-emerald-100 text-sm font-medium flex items-center gap-2"><i class="fa-regular fa-envelope"></i> {{ $user->email }}</p>
                </div>
            </div>
            
            <div class="flex gap-4">
                <div class="bg-black/20 backdrop-blur-sm border border-white/10 rounded-2xl p-4 min-w-[140px] flex items-center gap-4">
                    <div class="text-emerald-200 text-xl"><i class="fa-regular fa-id-badge"></i></div>
                    <div>
                        <div class="text-white font-bold text-sm uppercase">{{ str_replace('_', ' ', $user->role) }}</div>
                        <div class="text-emerald-200 text-[10px] uppercase tracking-widest font-bold">ROLE</div>
                    </div>
                </div>
                <div class="bg-black/20 backdrop-blur-sm border border-white/10 rounded-2xl p-4 min-w-[140px] flex items-center gap-4">
                    <div class="{{ $user->status === 'active' ? 'text-emerald-400' : 'text-red-400' }} text-xl"><i class="fa-solid fa-circle-dot"></i></div>
                    <div>
                        <div class="text-white font-bold text-sm uppercase">{{ $user->status }}</div>
                        <div class="text-emerald-200 text-[10px] uppercase tracking-widest font-bold">STATUS</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.roles.update', $user->id) }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @method('PUT')
        
        <!-- Left Column -->
        <div class="space-y-6">
            <!-- Overview Card -->
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-[#064e3b] text-white rounded-xl flex items-center justify-center shadow-sm"><i class="fa-regular fa-user"></i></div>
                    <div>
                        <h3 class="font-extrabold text-gray-900">Account Overview</h3>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Quick summary</p>
                    </div>
                </div>
                
                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 text-emerald-600 flex items-center justify-center text-sm"><i class="fa-regular fa-envelope"></i></div>
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Email</div>
                            <div class="text-sm font-bold text-gray-900">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 text-blue-600 flex items-center justify-center text-sm"><i class="fa-solid fa-phone"></i></div>
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Mobile</div>
                            <div class="text-sm font-bold text-gray-900">{{ $user->phone ?? 'Not provided' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 text-purple-600 flex items-center justify-center text-sm"><i class="fa-regular fa-building"></i></div>
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Company</div>
                            <div class="text-sm font-bold text-gray-900">{{ $user->company_name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-red-500' }} rounded-full"></div>
                        <h3 class="font-extrabold text-gray-900">Account Status</h3>
                    </div>
                    <span class="{{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }} text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">{{ $user->status }}</span>
                </div>
                
                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Role</span>
                        <span class="font-bold text-gray-900 uppercase">{{ str_replace('_', ' ', $user->role) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Joined</span>
                        <span class="font-bold text-gray-900">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-3xl border border-yellow-200 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center shadow-sm"><i class="fa-solid fa-wallet"></i></div>
                    <div>
                        <h3 class="font-extrabold text-gray-900">Wallet Balance</h3>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Available funds</p>
                    </div>
                </div>
                <div class="mt-4 bg-white rounded-xl p-4 border border-yellow-100 flex items-center gap-3">
                    <span class="text-gray-400 font-bold">₹</span>
                    <input type="number" step="0.01" name="wallet_balance" value="{{ $user->wallet_balance }}" class="w-full bg-transparent text-xl font-black text-gray-900 outline-none" required>
                </div>

                <!-- Instant Quick Recharge by Admin -->
                <div class="mt-5 pt-4 border-t border-yellow-200">
                    <p class="text-[11px] font-black text-amber-900 uppercase tracking-wider mb-2 flex items-center gap-1">
                        <i class="fa-solid fa-bolt text-amber-500"></i> Instant Add / Credit Funds
                    </p>
                    <div class="space-y-2">
                        <div class="flex gap-2">
                            <input type="number" form="adminQuickCreditForm" name="amount" id="quickAddAmt" placeholder="Enter ₹ Amount" min="1" step="1" class="flex-1 bg-white border border-amber-300 rounded-xl px-3 py-2 text-sm font-bold text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                            <button type="submit" form="adminQuickCreditForm" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-black text-xs transition shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                                <i class="fa-solid fa-plus-circle"></i> Add Now
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <button type="button" onclick="document.getElementById('quickAddAmt').value = 500;" class="px-2 py-1 bg-white border border-amber-200 rounded-lg text-[10px] font-extrabold text-amber-800 hover:bg-amber-100 transition">+ ₹500</button>
                            <button type="button" onclick="document.getElementById('quickAddAmt').value = 1000;" class="px-2 py-1 bg-white border border-amber-200 rounded-lg text-[10px] font-extrabold text-amber-800 hover:bg-amber-100 transition">+ ₹1,000</button>
                            <button type="button" onclick="document.getElementById('quickAddAmt').value = 2000;" class="px-2 py-1 bg-white border border-amber-200 rounded-lg text-[10px] font-extrabold text-amber-800 hover:bg-amber-100 transition">+ ₹2,000</button>
                            <button type="button" onclick="document.getElementById('quickAddAmt').value = 5000;" class="px-2 py-1 bg-white border border-amber-200 rounded-lg text-[10px] font-extrabold text-amber-800 hover:bg-amber-100 transition">+ ₹5,000</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column (Forms) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Info -->
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#064e3b] text-white rounded-xl flex items-center justify-center shadow-sm"><i class="fa-solid fa-user-pen"></i></div>
                        <div>
                            <h3 class="font-extrabold text-gray-900 text-lg">Personal Information</h3>
                            <p class="text-xs text-gray-500">Update name, email and contact details</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 relative">
                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Full Name <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-user text-gray-400"></i>
                            <input type="text" name="name" value="{{ $user->name }}" class="w-full bg-transparent text-sm font-bold text-gray-900 outline-none" required>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 relative">
                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Email Address <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-envelope text-gray-400"></i>
                            <input type="email" name="email" value="{{ $user->email }}" class="w-full bg-transparent text-sm font-bold text-gray-900 outline-none" required>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 relative">
                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Mobile Number</label>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-phone text-gray-400"></i>
                            <input type="text" name="phone" value="{{ $user->phone }}" class="w-full bg-transparent text-sm font-bold text-gray-900 outline-none">
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 relative">
                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Update Password</label>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                            <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full bg-transparent text-sm font-bold text-gray-900 outline-none placeholder-gray-400">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access & Configuration -->
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#064e3b] text-white rounded-xl flex items-center justify-center shadow-sm"><i class="fa-solid fa-shield-halved"></i></div>
                        <div>
                            <h3 class="font-extrabold text-gray-900 text-lg">Access & Configuration</h3>
                            <p class="text-xs text-gray-500">Configure system role, status, and company details</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 relative">
                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-2">System Role <span class="text-red-500">*</span></label>
                        <select name="role" class="w-full bg-transparent text-sm font-bold text-gray-900 outline-none cursor-pointer" required>
                            <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Super Admin (Full Control)</option>
                            <option value="operations" {{ $user->role == 'operations' ? 'selected' : '' }}>Admin / Operations</option>
                            <option value="seller" {{ $user->role == 'seller' ? 'selected' : '' }}>Seller (B2C)</option>
                            <option value="b2b_customer" {{ $user->role == 'b2b_customer' ? 'selected' : '' }}>B2B Customer</option>
                            <option value="franchise" {{ $user->role == 'franchise' ? 'selected' : '' }}>Franchise / Hub</option>
                            <option value="pickup_rider" {{ $user->role == 'pickup_rider' ? 'selected' : '' }}>Pickup Rider</option>
                            <option value="delivery_rider" {{ $user->role == 'delivery_rider' ? 'selected' : '' }}>Delivery Rider</option>
                        </select>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 relative">
                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-2">Account Status <span class="text-red-500">*</span></label>
                        <select name="status" class="w-full bg-transparent text-sm font-bold text-gray-900 outline-none cursor-pointer" required>
                            <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active (Allow Login)</option>
                            <option value="pending" {{ $user->status == 'pending' ? 'selected' : '' }}>Pending (Under Review)</option>
                            <option value="rejected" {{ $user->status == 'rejected' ? 'selected' : '' }}>Deactivated / Rejected</option>
                        </select>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 relative md:col-span-2">
                        <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block mb-1">Company / Hub Name</label>
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-building text-gray-400"></i>
                            <input type="text" name="company_name" value="{{ $user->company_name }}" placeholder="Required for Sellers and Franchises" class="w-full bg-transparent text-sm font-bold text-gray-900 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Area -->
            <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-3 pt-6 pb-28 sm:pb-16">
                <a href="{{ route('admin.roles.index') }}" class="order-2 sm:order-1 text-center px-6 py-3.5 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl shadow-sm hover:bg-gray-50 transition text-sm">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Back to User List
                </a>
                <button type="submit" class="order-1 sm:order-2 justify-center px-8 py-3.5 bg-[#064e3b] hover:bg-emerald-800 text-white font-extrabold rounded-xl shadow-lg hover:shadow-xl transition flex items-center gap-2 text-base">
                    <i class="fa-solid fa-floppy-disk text-lg"></i> Save All Changes
                </button>
            </div>
        </div>
    </form>

    <!-- Hidden Quick Credit Form -->
    <form id="adminQuickCreditForm" action="{{ route('admin.roles.wallet.credit', $user->id) }}" method="POST" style="display:none;">
        @csrf
        <input type="hidden" name="type" value="credit">
        <input type="hidden" name="remarks" value="Direct wallet recharge from Admin User Edit">
    </form>

    <!-- Mobile Sticky Floating Bottom Save Bar -->
    <div class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-gray-200 p-3 z-40 sm:hidden shadow-[0_-4px_25px_rgba(0,0,0,0.12)] flex gap-2">
        <a href="{{ route('admin.roles.index') }}" class="flex-1 text-center py-3 px-3 bg-gray-100 text-gray-700 font-bold rounded-xl text-xs flex items-center justify-center">
            Cancel
        </a>
        <button type="button" onclick="document.querySelector('form[action*=\"admin/roles/\"]').submit();" class="flex-[2] py-3 px-4 bg-[#064e3b] active:bg-emerald-950 text-white font-black rounded-xl text-xs flex items-center justify-center gap-2 shadow-md">
            <i class="fa-solid fa-floppy-disk text-sm"></i> Save All Changes
        </button>
    </div>
</div>
@endsection
