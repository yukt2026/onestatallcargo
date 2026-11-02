@extends('layouts.public')
@section('title', "Logistics & Transport Partner Network | OneStall Cargo")

@section('content')
<!-- 1. Hero Section -->
<section class="bg-gradient-to-r from-blue-50/40 via-white to-blue-50/40 py-12 md:py-16 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex flex-col lg:flex-row items-center gap-12">
            <div class="w-full lg:w-1/2 text-center lg:text-left">
                <div class="inline-block px-3 py-1 rounded-full border border-blue-200 bg-blue-50 text-brand-navy text-xs font-bold mb-4 uppercase tracking-wide">
                    Courier & Transport Network
                </div>
                <h1 class="text-3xl md:text-5xl font-extrabold text-brand-navy leading-tight mb-4">
                    Grow Your Fleet With <span class="text-brand-red">OneStall Partner Network</span>
                </h1>
                <p class="text-base md:text-lg text-gray-700 mb-8 font-medium leading-relaxed">
                    Join India's fastest-growing logistics and courier network. Connect your transport fleet, vehicle network, or regional logistics company to receive daily parcel loads and guaranteed automated payouts.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="#partner-form" class="px-8 py-3.5 rounded-full bg-brand-red text-white font-bold text-base hover:bg-brand-redHover transition shadow-md shadow-red-500/20">
                        Join as Transport Partner
                    </a>
                    <a href="{{ route('contact') }}" class="px-8 py-3.5 rounded-full border-2 border-brand-navy text-brand-navy font-bold text-base hover:bg-brand-navy hover:text-white transition">
                        Partner Enquiries
                    </a>
                </div>
            </div>

            <div class="w-full lg:w-1/2 relative">
                <div class="relative rounded-2xl overflow-hidden shadow-xl border-4 border-white bg-white">
                    <img src="{{ asset('images/dashboard.jpg') }}" alt="Transport Partner Network" class="w-full h-80 md:h-96 object-cover">
                </div>
                <div class="absolute -bottom-4 -left-4 bg-white p-3.5 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3 z-20">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 font-medium">Network Size</div>
                        <div class="text-sm font-bold text-brand-navy">15+ Integrated Carriers</div>
                    </div>
                </div>
                <div class="absolute -top-4 -right-4 bg-white p-3.5 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3 z-20">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-brand-navy font-bold">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 font-medium">Daily Loads</div>
                        <div class="text-sm font-bold text-brand-red">Guaranteed Order Density</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Integrated Carriers Grid -->
<!-- <section class="py-12 bg-white border-y border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-bold text-brand-navy mb-8">Our Integrated Logistics Partners</h2>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-6 items-center opacity-80">
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 font-black text-gray-700 text-lg">Delhivery</div>
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 font-black text-gray-700 text-lg">BlueDart</div>
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 font-black text-gray-700 text-lg">XpressBees</div>
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 font-black text-gray-700 text-lg">Ecom Express</div>
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 font-black text-gray-700 text-lg">Shadowfax</div>
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 font-black text-gray-700 text-lg">DTDC</div>
        </div>
    </div>
</section> -->

<!-- 3. Why Partner With Us Grid -->
<section class="py-12 md:py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-2xl md:text-4xl font-extrabold text-brand-navy mb-3">
                Why Carriers & Fleet Owners Partner With OneStall
            </h2>
            <p class="text-base text-gray-600">
                Maximize vehicle utilization and receive reliable, automated weekly freight payments.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50/70 p-6 rounded-2xl border border-gray-100 hover:shadow-md transition text-center">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-brand-navy flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">High Volume Order Density</h3>
                <p class="text-sm text-gray-600">Access thousands of pre-paid and COD shipments generated daily by our seller network across India.</p>
            </div>

            <div class="bg-gray-50/70 p-6 rounded-2xl border border-gray-100 hover:shadow-md transition text-center">
                <div class="w-12 h-12 rounded-xl bg-red-100 text-brand-red flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">On-Time Weekly Settlements</h3>
                <p class="text-sm text-gray-600">Automated freight payout clearing system ensuring your transport account is credited every week without delay.</p>
            </div>

            <div class="bg-gray-50/70 p-6 rounded-2xl border border-gray-100 hover:shadow-md transition text-center">
                <div class="w-12 h-12 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-route"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Optimized Route Allocation</h3>
                <p class="text-sm text-gray-600">Smart routing algorithms match your specific vehicle types (bikes, 3-wheelers, 14ft trucks) with ideal cargo routes.</p>
            </div>
        </div>
    </div>
</section>

<!-- 4. Partner Application Form -->
<section id="partner-form" class="py-12 md:py-16 bg-gray-50/50 border-t border-gray-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-gray-200">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-brand-navy">Partner Onboarding Form</h3>
                <p class="text-xs text-gray-500 font-medium">Submit your fleet or agency details to join our carrier integration roster</p>
            </div>

            <form class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Company / Agency Name</label>
                        <input type="text" placeholder="Apex Logistics Pvt Ltd" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-brand-navy">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Contact Person Name</label>
                        <input type="text" placeholder="Vikram Singh" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-brand-navy">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" placeholder="9876543210" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-brand-navy">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Fleet / Service Type</label>
                        <select class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-brand-navy">
                            <option>National Express Courier</option>
                            <option>Regional B2B Cargo / Trucking</option>
                            <option>Hyperlocal Bike Rider Fleet</option>
                            <option>Air Freight Forwarder</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-brand-red text-white font-extrabold text-sm hover:bg-brand-redHover transition shadow-md mt-2">
                    Submit Partner Application &rarr;
                </button>
            </form>
        </div>
    </div>
</section>
@endsection