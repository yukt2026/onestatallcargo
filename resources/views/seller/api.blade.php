@extends('layouts.seller')
@section('title', 'Developer API - OneStall Cargo')
@section('content')
<div class="max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">OneStall Developer API</h1>
        <p class="text-sm text-gray-500 mt-1">Integrate your eCommerce platform (Shopify, WooCommerce, ERP) directly with OneStall Cargo.</p>
    </div>

    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-4 sm:p-6 space-y-6">
        <div>
            <h3 class="font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Production API Token</h3>
            
            @if(session('new_token'))
                <div class="bg-green-50 p-4 rounded-xl border border-green-200 mb-4">
                    <p class="text-sm font-bold text-green-800 mb-2">Here is your new API Token. Copy it now, it won't be shown again!</p>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input type="text" id="newToken" value="{{ session('new_token') }}" readonly class="w-full bg-white border border-green-300 rounded-lg p-2 font-mono text-sm text-gray-800 focus:outline-none">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('newToken').value); alert('Copied!')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold whitespace-nowrap">
                            <i class="fa-regular fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            @elseif($user->api_token)
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 font-mono text-sm break-all mb-4">
                    {{ substr($user->api_token, 0, 15) }}...{{ substr($user->api_token, -5) }}
                </div>
            @else
                <div class="bg-yellow-50 text-yellow-800 p-4 rounded-xl border border-yellow-200 mb-4 text-sm font-bold">
                    You have not generated an API key yet.
                </div>
            @endif

            <form action="{{ route('seller.api-keys.generate') }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-[#1e293b] text-white shadow-md hover:bg-black transition-colors">
                    <i class="fa-solid fa-key mr-2"></i> {{ $user->api_token ? 'Regenerate Token' : 'Generate API Token' }}
                </button>
            </form>
            <p class="text-xs text-red-500 mt-2 font-bold"><i class="fa-solid fa-triangle-exclamation"></i> Warning: Regenerating your token will break any active integrations.</p>
        </div>
    </div>

    <!-- API Docs Quick Reference -->
    <div class="bg-[#1e293b] rounded-3xl p-4 sm:p-6 text-white space-y-4">
        <h3 class="font-bold text-lg"><i class="fa-solid fa-book"></i> API Quick Reference</h3>
        <p class="text-sm text-gray-300">Use your token as a Bearer Token in the Authorization header.</p>
        
        <div class="space-y-2 text-sm mt-4">
            <div class="bg-black/30 p-3 rounded-lg"><span class="text-green-400 font-bold mr-2">POST</span> <code class="break-all">{{ url('api/v1/rates') }}</code></div>
            <div class="bg-black/30 p-3 rounded-lg"><span class="text-green-400 font-bold mr-2">POST</span> <code class="break-all">{{ url('api/v1/shipments') }}</code></div>
            <div class="bg-black/30 p-3 rounded-lg"><span class="text-blue-400 font-bold mr-2">GET</span> <code class="break-all">{{ url('api/v1/shipments') }}</code></div>
            <div class="bg-black/30 p-3 rounded-lg"><span class="text-blue-400 font-bold mr-2">GET</span> <code class="break-all">{{ url('api/v1/shipments/{awb}') }}</code></div>
            <div class="bg-black/30 p-3 rounded-lg"><span class="text-yellow-400 font-bold mr-2">POST</span> <code class="break-all">{{ url('api/v1/shipments/{awb}/cancel') }}</code></div>
            
            <div class="mt-4 pt-4 border-t border-gray-700/50">
                <p class="text-xs text-gray-400 mb-2">External Courier & Logistics APIs (Legacy Auth):</p>
                <div class="bg-black/30 p-3 rounded-lg"><span class="text-green-400 font-bold mr-2">POST</span> <code class="break-all">{{ url('api/v1/external/serviceability') }}</code></div>
                <div class="bg-black/30 p-3 rounded-lg"><span class="text-green-400 font-bold mr-2">POST</span> <code class="break-all">{{ url('api/v1/external/rate') }}</code></div>
                <div class="bg-black/30 p-3 rounded-lg"><span class="text-green-400 font-bold mr-2">POST</span> <code class="break-all">{{ url('api/v1/external/shipment') }}</code></div>
                <div class="bg-black/30 p-3 rounded-lg"><span class="text-blue-400 font-bold mr-2">GET</span> <code class="break-all">{{ url('api/v1/external/track/{awb}') }}</code></div>
            </div>
        </div>
        
        <a href="{{ url('api-docs') }}" class="inline-block mt-4 text-[#E8027D] text-sm font-bold hover:underline">View Full Interactive Documentation &rarr;</a>
    </div>
</div>
@endsection

