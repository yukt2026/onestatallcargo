@extends('layouts.public')
@section('title', 'Contact OneStall Cargo | Direct Logistics & Courier Partner')
@section('meta_description', 'Contact OneStall Cargo for express courier booking, B2B cargo shipping, franchise logistics hubs, and enterprise supply chain partnerships.')
@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 class="text-3xl font-extrabold text-brand-navy mb-8 text-center">Contact Us</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8">
                <form action="{{ route('contact.post') }}" method="POST" class="space-y-6">
                    @csrf
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                            {{ session('success') }}
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Company / Volume</label>
                        <input type="text" name="company_name" placeholder="Company Name" class="w-full px-4 py-2 mb-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                        <select name="volume" class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                            <option value="">Select Monthly Volume</option>
                            <option value="1-100">1 - 100 Shipments</option>
                            <option value="101-500">101 - 500 Shipments</option>
                            <option value="500+">500+ Shipments</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Message</label>
                        <textarea name="message" required rows="4" class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-blue"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-brand-navy hover:bg-black text-white font-bold py-3 rounded-xl transition">Submit Inquiry</button>
                </form>
            </div>
            
            <div class="flex flex-col justify-start pt-4">
                <!-- Company Identity -->
                <div class="mb-6">
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-brand-red mb-2 block">Registered Office</span>
                    <h2 class="text-xl font-extrabold text-gray-900 mb-1">ANYURVA AYURVEDA PRIVATE LIMITED</h2>
                    <p class="text-xs text-gray-400 font-medium mb-4">CIN: U21003UP2024PTC205462</p>
                </div>

                <!-- Contact Details -->
                <div class="space-y-3 mb-8">
                    <div class="flex items-start gap-3 text-gray-600">
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-location-dot text-brand-blue text-sm"></i>
                        </div>
                        <div class="text-sm leading-relaxed">
                            569/153 KHA, Barigawan, KP Road,<br>
                            32 BN PAC, Lucknow – 226023<br>
                            Uttar Pradesh, India
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-gray-600">
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-envelope text-brand-blue text-sm"></i>
                        </div>
                        <a href="mailto:support@onestallcargo.com" class="text-sm hover:text-brand-blue transition">support@onestallcargo.com</a>
                    </div>
                    <div class="flex items-center gap-3 text-gray-600">
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-phone text-brand-blue text-sm"></i>
                        </div>
                        <a href="tel:18001234567" class="text-sm hover:text-brand-blue transition">1800-123-4567</a>
                    </div>
                </div>

                <!-- Google Map Embed – Lucknow area -->
                <div class="rounded-2xl overflow-hidden border border-gray-200 shadow-sm">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3559.6787!2d80.9462!3d26.8467!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x399bfd991f32b16b%3A0x93ccba8909978be7!2sLucknow%2C%20Uttar%20Pradesh!5e0!3m2!1sen!2sin!4v1695000000000"
                        width="100%" height="220" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        class="w-full">
                    </iframe>
                </div>

                <!-- Business Hours -->
                <div class="mt-6 bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <p class="text-xs font-extrabold text-gray-700 uppercase tracking-wider mb-2"><i class="fa-solid fa-clock mr-1 text-brand-blue"></i> Support Hours</p>
                    <p class="text-sm text-gray-600">Monday – Saturday: <span class="font-semibold text-gray-800">9:00 AM – 7:00 PM</span></p>
                    <p class="text-sm text-gray-600">Sunday: <span class="font-semibold text-gray-800">10:00 AM – 4:00 PM</span></p>
                </div>
            </div>
        </div>
    </div>
@endsection
