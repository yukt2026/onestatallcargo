@extends('layouts.hub')
@section('title', 'Bulk Booking - OneStall Cargo')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Bulk Upload Bookings</h1>
        <p class="text-sm text-gray-500 mt-1">Upload an Excel or CSV file to book 100+ orders instantly via our OneStall Express Carrier API.</p>
    </div>

    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 md:p-12 text-center">
        @php
            $kycApproved = Auth::user()->isKycApproved();
        @endphp

        @if(!$kycApproved)
            <div class="bg-red-50 border border-red-100 rounded-xl p-8 text-center max-w-2xl mx-auto shadow-sm">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-lock text-2xl"></i>
                </div>
                <h2 class="font-black text-red-900 text-xl mb-3">KYC Verification Required</h2>
                <p class="text-sm text-red-700 mb-6 font-medium leading-relaxed">
                    To comply with logistics regulations and prevent fraud, all sellers must complete their KYC verification before creating bulk orders.
                </p>
                <a href="{{ route('hub.profile') }}?view=kyc" class="inline-flex items-center gap-2 px-6 py-3 bg-[#1e1b4b] text-white text-sm font-bold rounded-xl hover:bg-black transition shadow-md">
                    <i class="fa-solid fa-id-card"></i> Complete KYC Now
                </a>
            </div>
        @else
            <form action="{{ route('hub.bulk.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <i class="fa-solid fa-file-csv text-6xl text-gray-300 mb-4"></i>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Select CSV File</label>
                <input type="file" name="bulk_file" accept=".csv" required class="block w-full max-w-sm mx-auto text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-[#E8027D] file:text-white hover:file:bg-[#d60070] transition">
            </div>

            <div class="bg-blue-50 text-blue-800 p-4 rounded-xl text-sm font-bold max-w-md mx-auto text-left">
                <div class="flex justify-between items-start mb-2">
                    <div><i class="fa-solid fa-info-circle mr-1"></i> Ensure your CSV has the following headers:</div>
                    <a href="{{ asset('downloads/sample_bulk_order.csv') }}" download class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-full whitespace-nowrap shadow-sm transition"><i class="fa-solid fa-download"></i> Sample CSV</a>
                </div>
                <ul class="list-disc pl-5 font-normal text-xs text-blue-700">
                    <li>pickup_pincode, shipment_type</li>
                    <li>receiver_name, receiver_phone, delivery_address, delivery_city, delivery_pincode</li>
                    <li>weight_kg, length_cm, width_cm, height_cm</li>
                    <li>is_cod (1 or 0), invoice_value</li>
                    <li>product_name, product_sku, product_qty</li>
                </ul>
            </div>

            <button type="submit" class="px-8 py-3 rounded-xl text-sm font-bold bg-[#1e293b] text-white shadow-md hover:bg-black transition-colors">
                <i class="fa-solid fa-cloud-arrow-up mr-2"></i> Process Bulk Upload
            </button>
        </form>
        @endif
    </div>
</div>
@endsection


