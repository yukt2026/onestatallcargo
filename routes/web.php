<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;

Route::get('/', function () { 
    $services = \App\Models\Service::where('is_active', true)->get();
    $testimonials = \App\Models\Testimonial::where('is_active', true)->get();
    $faqs = \App\Models\Faq::where('is_active', true)->orderBy('sort_order')->get();
    return view('welcome', compact('services', 'testimonials', 'faqs')); 
});
Route::post('/contact', [\App\Http\Controllers\PublicContactController::class, 'store'])->name('contact.post');
Route::get('/contact', function () { return view('contact'); })->name('contact');
Route::get('/services', function () { 
    $services = \App\Models\Service::where('is_active', true)->get();
    return view('services', compact('services')); 
})->name('services');
Route::get('/rider/register', [\App\Http\Controllers\RiderRegistrationController::class, 'create'])->name('rider.register');
Route::post('/rider/register', [\App\Http\Controllers\RiderRegistrationController::class, 'store'])->name('rider.store');


Route::post('/franchise', [\App\Http\Controllers\FranchiseController::class, 'store'])->name('franchise.store');

Route::get('/track', [\App\Http\Controllers\TrackController::class, 'index'])->name('track');
Route::post('/track', [\App\Http\Controllers\TrackController::class, 'track'])->name('track.post');
Route::get('/api-docs', function () { return redirect()->route('docs'); })->name('api-docs');
Route::get('/pricing', function () { return view('pricing'); })->name('pricing');
Route::get('/privacy', function () { return view('legal', ['title' => 'Privacy Policy']); })->name('privacy');
Route::get('/terms', function () { return view('legal', ['title' => 'Terms of Service']); })->name('terms');
Route::get('/cookies', function () { return view('legal', ['title' => 'Cookie Policy']); })->name('cookies');
Route::get('/franchise', function () { return view('franchise'); })->name('franchise');


// Public Redesign Routes
Route::get('/solutions/b2c-shipping', function () { return view('public.solutions.b2c'); })->name('solutions.b2c');
Route::get('/solutions/b2b-cargo', function () { return view('public.solutions.b2b'); })->name('solutions.b2b');
Route::get('/solutions/international-shipping', function () { return view('public.solutions.international'); })->name('solutions.international');
Route::get('/solutions/quick-delivery', function () { return view('public.solutions.quick'); })->name('solutions.quick');
Route::redirect('/solutions/courier-aggregation', '/solutions/b2c-shipping', 301)->name('solutions.aggregation');
Route::get('/solutions/ecommerce-integration', function () { return view('public.solutions.ecommerce'); })->name('solutions.ecommerce');

Route::get('/platform/video-evidence', function () { return view('public.platform.evidence'); })->name('platform.evidence');
Route::get('/platform/ndr-management', function () { return view('public.platform.ndr'); })->name('platform.ndr');
Route::get('/platform/rto-management', function () { return view('public.platform.rto'); })->name('platform.rto');
Route::get('/platform/cod-settlement', function () { return view('public.platform.cod'); })->name('platform.cod');
Route::get('/platform/live-tracking', function () { return view('public.platform.tracking'); })->name('platform.tracking');
Route::get('/platform/awb-labels', function () { return view('public.platform.awb'); })->name('platform.awb');

Route::get('/developers', function () { return view('public.developers.index'); })->name('developers');
Route::get('/docs/postman', function () {
    $apiDocs = \App\Models\ApiDoc::where('is_active', true)->orderBy('sort_order')->get();
    
    $item = [];
    foreach ($apiDocs as $doc) {
        $item[] = [
            'name' => $doc->title,
            'request' => [
                'method' => $doc->method,
                'header' => [
                    ['key' => 'Authorization', 'value' => 'Bearer {{api_key}}', 'type' => 'text']
                ],
                'url' => [
                    'raw' => '{{base_url}}' . $doc->path,
                    'host' => ['{{base_url}}'],
                    'path' => explode('/', ltrim($doc->path, '/'))
                ],
                'description' => $doc->description
            ]
        ];
    }
    
    $postman = [
        'info' => [
            'name' => 'OneStall Cargo API',
            'description' => 'Interactive RESTful API for developers.',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
        ],
        'item' => $item,
        'variable' => [
            ['key' => 'base_url', 'value' => 'https://api.onestallcargo.com/api', 'type' => 'string'],
            ['key' => 'api_key', 'value' => 'OSC_YOUR_KEY', 'type' => 'string']
        ]
    ];
    return response()->json($postman)->header('Content-Disposition', 'attachment; filename="onestall_cargo_postman.json"');
})->name('docs.postman');

Route::get('/docs/openapi', function () {
    $apiDocs = \App\Models\ApiDoc::where('is_active', true)->orderBy('sort_order')->get();
    
    $paths = [];
    foreach ($apiDocs as $doc) {
        $method = strtolower($doc->method);
        $paths[$doc->path] = [
            $method => [
                'summary' => $doc->title,
                'description' => $doc->description,
                'responses' => [
                    '200' => ['description' => 'Successful operation']
                ]
            ]
        ];
    }
    
    $openapi = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'OneStall Cargo API',
            'version' => '1.0.0',
            'description' => 'API reference for integrating OneStall Cargo logistics into your application.'
        ],
        'servers' => [
            ['url' => 'https://api.onestallcargo.com/api']
        ],
        'paths' => $paths
    ];
    return response()->json($openapi)->header('Content-Disposition', 'attachment; filename="onestall_cargo_openapi.json"');
})->name('docs.openapi');

Route::get('/docs', function () {
    $apiDocs = \App\Models\ApiDoc::where('is_active', true)->orderBy('sort_order')->get();
    if ($apiDocs->isEmpty()) {
        (new \Database\Seeders\ApiDocSeeder())->run();
        $apiDocs = \App\Models\ApiDoc::where('is_active', true)->orderBy('sort_order')->get();
    }
    return view('public.developers.docs', compact('apiDocs'));
})->name('docs');

Route::get('/corporate', function () { return view('public.corporate'); })->name('corporate');
Route::get('/partners', function () { return view('public.partners'); })->name('partners');
Route::get('/about', function () { return view('public.about'); })->name('about');
Route::get('/faq', function (\Illuminate\Http\Request $request) {
    $query = $request->get('q'); 
    $faqs = \App\Models\Faq::where('is_active', true)->orderBy('sort_order')->get();
    return view('public.faq', compact('faqs')); 
})->name('faq');
Route::get('/help', function () { return view('public.help'); })->name('help');
// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login/google', [AuthController::class, 'redirectToGoogle'])->name('login.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::get('/forgot-password', function () { return view('auth.forgot-password'); })->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', function (Illuminate\Http\Request $request, $token) { return view('auth.reset-password', ['token' => $token, 'email' => $request->email]); })->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\AuthController::class, 'resetPassword'])->name('password.update');

// Dashboard Routes
Route::middleware(['auth'])->group(function () {
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () { 
        return redirect('/login'); // Let AuthController redirect based on role instead, or just point to login logic
    })->name('dashboard');

    // ADMIN PORTAL (Requires Admin Role, but handled by Admin role itself)
    Route::middleware(['role:admin,operations'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::resource('api_docs', \App\Http\Controllers\AdminApiDocController::class, ['as' => 'admin']);
        Route::get('/contacts', [\App\Http\Controllers\AdminContactController::class, 'index'])->name('admin.contacts.index');
        Route::post('/contacts/{id}', [\App\Http\Controllers\AdminContactController::class, 'action'])->name('admin.contacts.action');
        Route::get('/live-map', [\App\Http\Controllers\AdminController::class, 'liveMap'])->name('admin.map');
        
        Route::get('/shipments', [\App\Http\Controllers\AdminShipmentController::class, 'index'])->name('admin.shipments.index');
        Route::get('/shipments/create', [\App\Http\Controllers\AdminShipmentController::class, 'create'])->name('admin.shipments.create');
        Route::post('/shipments', [\App\Http\Controllers\AdminShipmentController::class, 'store'])->name('admin.shipments.store');
        Route::get('/shipments/{awb}/label', [\App\Http\Controllers\AdminShipmentController::class, 'printLabel'])->name('admin.shipments.label');
        Route::get('/shipments/{awb}/slip', [\App\Http\Controllers\AdminShipmentController::class, 'printLabel'])->name('admin.shipments.slip');
        Route::match(['get', 'post'], '/shipments/bulk-print-slips', [\App\Http\Controllers\AdminShipmentController::class, 'bulkPrintLabels'])->name('admin.shipments.bulk-print');
        Route::get('/shipments/{awb}/invoice', [\App\Http\Controllers\AdminShipmentController::class, 'printInvoice'])->name('admin.shipments.invoice');
        Route::get('/shipments/{awb}/lr', [\App\Http\Controllers\AdminShipmentController::class, 'printLR'])->name('admin.shipments.lr');
        Route::post('/shipments/{id}/cancel', [\App\Http\Controllers\AdminShipmentController::class, 'cancel'])->name('admin.shipments.cancel');
        
        Route::resource('serviceability', \App\Http\Controllers\AdminServiceabilityController::class, ['as' => 'admin']);
        Route::get('/pickups', [\App\Http\Controllers\AdminPickupController::class, 'index'])->name('admin.pickups.index');
        Route::post('/pickups/assign', [\App\Http\Controllers\AdminPickupController::class, 'assignRider'])->name('admin.pickups.assign');
        Route::get('/ndr', [\App\Http\Controllers\AdminNDRController::class, 'index'])->name('admin.ndr.index');
        Route::post('/ndr/{id}', [\App\Http\Controllers\AdminNDRController::class, 'action'])->name('admin.ndr.action');
        Route::get('/evidence', [\App\Http\Controllers\AdminEvidenceController::class, 'index'])->name('admin.evidence.index');
        
        Route::get('/hubs', [\App\Http\Controllers\AdminHubController::class, 'index'])->name('admin.hubs.index');
        Route::get('/pincodes', [\App\Http\Controllers\AdminPincodeController::class, 'index'])->name('admin.pincodes.index');
        Route::post('/pincodes', [\App\Http\Controllers\AdminPincodeController::class, 'store'])->name('admin.pincodes.store');
        Route::post('/pincodes/import', [\App\Http\Controllers\AdminPincodeController::class, 'import'])->name('admin.pincodes.import');
        Route::post('/pincodes/{id}/delete', [\App\Http\Controllers\AdminPincodeController::class, 'destroy'])->name('admin.pincodes.destroy');
        Route::post('/hubs', [\App\Http\Controllers\AdminHubController::class, 'store'])->name('admin.hubs.store');
        Route::post('/hubs/manager', [\App\Http\Controllers\AdminHubController::class, 'storeManager'])->name('admin.hubs.store_manager');
        Route::get('/hubs/{id}/edit', [\App\Http\Controllers\AdminHubController::class, 'edit'])->name('admin.hubs.edit');
        Route::post('/hubs/{id}/update', [\App\Http\Controllers\AdminHubController::class, 'update'])->name('admin.hubs.update');
        Route::post('/hubs/{id}/toggle', [\App\Http\Controllers\AdminHubController::class, 'toggle'])->name('admin.hubs.toggle');
        Route::post('/hubs/franchise/{id}/approve', [\App\Http\Controllers\AdminHubController::class, 'approveFranchise'])->name('admin.hubs.approve_franchise');
        Route::post('/hubs/franchise/{id}/reject', [\App\Http\Controllers\AdminHubController::class, 'rejectFranchise'])->name('admin.hubs.reject_franchise');
        
        // Franchise Hierarchy Routes (Phase 2 & 360 Overview)
        Route::post('/franchises/state', [\App\Http\Controllers\AdminHubController::class, 'storeStateFranchise'])->name('admin.franchises.store_state');
        Route::post('/franchises/district', [\App\Http\Controllers\AdminHubController::class, 'storeDistrictFranchise'])->name('admin.franchises.store_district');
        Route::get('/franchises/{id}/overview', [\App\Http\Controllers\AdminHubController::class, 'overviewFranchise'])->name('admin.franchises.overview');
        Route::get('/franchises/{id}/edit', [\App\Http\Controllers\AdminHubController::class, 'editFranchise'])->name('admin.franchises.edit');
        Route::post('/franchises/{id}/update', [\App\Http\Controllers\AdminHubController::class, 'updateFranchise'])->name('admin.franchises.update');
        Route::post('/franchises/{id}/toggle', [\App\Http\Controllers\AdminHubController::class, 'toggleFranchise'])->name('admin.franchises.toggle');
        Route::post('/franchises/{id}/wallet/credit', [\App\Http\Controllers\AdminHubController::class, 'creditFranchiseWallet'])->name('admin.franchises.wallet.credit');
        
                Route::get('/customers', [\App\Http\Controllers\AdminCustomerController::class, 'index'])->name('admin.customers.index');
        Route::post('/customers', [\App\Http\Controllers\AdminCustomerController::class, 'store'])->name('admin.customers.store');
        Route::post('/customers/{id}', [\App\Http\Controllers\AdminCustomerController::class, 'update'])->name('admin.customers.update');
        Route::delete('/customers/{id}', [\App\Http\Controllers\AdminCustomerController::class, 'destroy'])->name('admin.customers.destroy');
        Route::post('/customers/{id}/toggle', [\App\Http\Controllers\AdminCustomerController::class, 'toggle'])->name('admin.customers.toggle');
        Route::get('/sellers', [\App\Http\Controllers\AdminSellerController::class, 'index'])->name('admin.sellers.index');
        Route::post('/sellers', [\App\Http\Controllers\AdminSellerController::class, 'store'])->name('admin.sellers.store');
        Route::post('/sellers/{id}', [\App\Http\Controllers\AdminSellerController::class, 'update'])->name('admin.sellers.update');
        Route::post('/sellers/{id}/toggle', [\App\Http\Controllers\AdminSellerController::class, 'toggle'])->name('admin.sellers.toggle');
        Route::delete('/sellers/{id}', [\App\Http\Controllers\AdminSellerController::class, 'destroy'])->name('admin.sellers.destroy');
        
        
        
        
        
        
        Route::get('/riders', [\App\Http\Controllers\AdminRiderController::class, 'index'])->name('admin.riders.index');
        Route::post('/riders', [\App\Http\Controllers\AdminRiderController::class, 'store'])->name('admin.riders.store');
        Route::post('/riders/{id}/update', [\App\Http\Controllers\AdminRiderController::class, 'update'])->name('admin.riders.update');
        Route::get('/riders/{id}/report', [\App\Http\Controllers\AdminRiderController::class, 'report'])->name('admin.riders.report');
        Route::post('/riders/{id}/delete', [\App\Http\Controllers\AdminRiderController::class, 'destroy'])->name('admin.riders.destroy');
        Route::post('/billing/remit/{userId}', [\App\Http\Controllers\AdminBillingController::class, 'remit'])->name('admin.billing.remit');
        Route::post('/billing/remit-early/{userId}', [\App\Http\Controllers\AdminBillingController::class, 'remitEarlyCod'])->name('admin.billing.remit-early');
        
                        
        
        
        
        
        Route::get('/reports', [\App\Http\Controllers\AdminReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/reports/export', [\App\Http\Controllers\AdminReportController::class, 'exportCsv'])->name('admin.reports.export');
        Route::get('/weight-discrepancies', [\App\Http\Controllers\AdminWeightController::class, 'index'])->name('admin.weight');
        Route::post('/weight-discrepancies/import', [\App\Http\Controllers\AdminWeightController::class, 'import'])->name('admin.weight.import');
        Route::post('/weight-discrepancies/{id}/action', [\App\Http\Controllers\AdminWeightController::class, 'action'])->name('admin.weight.action');
        Route::get('/weight-freeze', [\App\Http\Controllers\AdminWeightFreezeController::class, 'index'])->name('admin.weight.freeze');
        Route::post('/weight-freeze/{id}/action', [\App\Http\Controllers\AdminWeightFreezeController::class, 'action'])->name('admin.weight.freeze.action');
        // Admin KYC Verification
        Route::get('/banners', [\App\Http\Controllers\AdminBannerController::class, 'index'])->name('admin.banners.index');
        Route::post('/banners', [\App\Http\Controllers\AdminBannerController::class, 'store'])->name('admin.banners.store');

        // CMS / Frontend CRUDs
        Route::resource('faqs', \App\Http\Controllers\AdminFaqController::class)->names('admin.faqs');
        Route::resource('services', \App\Http\Controllers\AdminServiceController::class)->names('admin.services');
        Route::resource('testimonials', \App\Http\Controllers\AdminTestimonialController::class)->names('admin.testimonials');
        
        Route::get('/kyc', [\App\Http\Controllers\KycController::class, 'adminIndex'])->name('admin.kyc.index');
        Route::post('/kyc/{id}/approve', [\App\Http\Controllers\KycController::class, 'adminApprove'])->name('admin.kyc.approve');
        Route::post('/kyc/{id}/reject', [\App\Http\Controllers\KycController::class, 'adminReject'])->name('admin.kyc.reject');
        Route::post('/kyc/{id}/warehouse', [\App\Http\Controllers\KycController::class, 'adminCreateWarehouse'])->name('admin.kyc.warehouse');
        // Strictly Super Admin Only Routes
        Route::middleware(['role:admin'])->group(function() {
            Route::resource('settings', \App\Http\Controllers\AdminSettingController::class)->names('admin.settings');
            Route::get('/roles', [\App\Http\Controllers\AdminRoleController::class, 'index'])->name('admin.roles.index');
            Route::get('/roles/create', [\App\Http\Controllers\AdminRoleController::class, 'create'])->name('admin.roles.create');
            Route::post('/roles', [\App\Http\Controllers\AdminRoleController::class, 'store'])->name('admin.roles.store');
            Route::get('/roles/{id}/edit', [\App\Http\Controllers\AdminRoleController::class, 'edit'])->name('admin.roles.edit');
            Route::put('/roles/{id}', [\App\Http\Controllers\AdminRoleController::class, 'update'])->name('admin.roles.update');
            Route::post('/roles/{id}/wallet/credit', [\App\Http\Controllers\AdminRoleController::class, 'creditWallet'])->name('admin.roles.wallet.credit');
            Route::get('/billing', [\App\Http\Controllers\AdminBillingController::class, 'index'])->name('admin.billing.index');
            Route::post('/billing/{id}/pay', [\App\Http\Controllers\AdminBillingController::class, 'recordPayment'])->name('admin.billing.pay');
            Route::get('/billing/invoices', [\App\Http\Controllers\AdminBillingController::class, 'invoices'])->name('admin.billing.invoices');
            // Rate Cards
            Route::post('/ratecards/{id}/duplicate', [\App\Http\Controllers\AdminRateCardController::class, 'duplicate'])->name('admin.ratecards.duplicate');
            Route::post('/ratecards/{id}/activate', [\App\Http\Controllers\AdminRateCardController::class, 'activate'])->name('admin.ratecards.activate');
            Route::post('/ratecards/{id}/preview', [\App\Http\Controllers\AdminRateCardController::class, 'preview'])->name('admin.ratecards.preview');
            Route::resource('ratecards', \App\Http\Controllers\AdminRateCardController::class, ['as' => 'admin']);

            // Rates (Legacy Rate Rules)
            Route::get('/rates', [\App\Http\Controllers\AdminRateController::class, 'index'])->name('admin.rates.index');
            Route::post('/rates', [\App\Http\Controllers\AdminRateController::class, 'store'])->name('admin.rates.store');
            Route::put('/rates/{id}', [\App\Http\Controllers\AdminRateController::class, 'update'])->name('admin.rates.update');

            // Couriers
            Route::get('/couriers', [\App\Http\Controllers\AdminCourierController::class, 'index'])->name('admin.couriers.index');
            Route::post('/couriers', [\App\Http\Controllers\AdminCourierController::class, 'store'])->name('admin.couriers.store');
            Route::post('/couriers/{id}/update', [\App\Http\Controllers\AdminCourierController::class, 'update'])->name('admin.couriers.update');
            Route::post('/couriers/{id}/toggle', [\App\Http\Controllers\AdminCourierController::class, 'toggle'])->name('admin.couriers.toggle');

            // Integrations
            Route::get('/integrations', [\App\Http\Controllers\AdminIntegrationController::class, 'index'])->name('admin.integrations');
            Route::post('/integrations', [\App\Http\Controllers\AdminIntegrationController::class, 'save'])->name('admin.integrations.save');
        });

    });

    // SELLER PORTAL (Requires Seller Role)
    Route::middleware(['role:seller,aggregator,b2b_customer,corporate', 'check.kyc'])->prefix('seller')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SellerDashboardController::class, 'index'])->name('seller.dashboard');
        
        // KYC Submission
        Route::post('/kyc/submit', [\App\Http\Controllers\KycController::class, 'submit'])->name('seller.kyc.submit');
        
        // Premium Views
        Route::get('/tools', [\App\Http\Controllers\SellerDashboardController::class, 'tools'])->name('seller.tools');
        Route::get('/early-cod', [\App\Http\Controllers\SellerDashboardController::class, 'earlyCod'])->name('seller.early-cod');
        Route::post('/early-cod/activate', [\App\Http\Controllers\SellerDashboardController::class, 'activateEarlyCod'])->name('seller.early-cod.activate');
        Route::post('/tools/pincodes/export', [\App\Http\Controllers\SellerDashboardController::class, 'exportPincodes'])->name('seller.tools.pincodes.export');
        Route::view('/settings', 'seller.settings')->name('seller.settings');
        
        // Settings API
        Route::post('/settings/profile', [\App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('seller.settings.profile');
        Route::post('/settings/bank', [\App\Http\Controllers\SettingsController::class, 'updateBankDetails'])->name('seller.settings.bank');
        Route::post('/settings/password', [\App\Http\Controllers\SettingsController::class, 'updatePassword'])->name('seller.settings.password');
        Route::post('/settings/warehouses', [\App\Http\Controllers\SettingsController::class, 'createWarehouse'])->name('seller.settings.warehouses');
        Route::put('/settings/warehouses/{id}', [\App\Http\Controllers\SettingsController::class, 'updateWarehouse'])->name('seller.settings.warehouses.update');
        Route::delete('/settings/warehouses/{id}', [\App\Http\Controllers\SettingsController::class, 'deleteWarehouse'])->name('seller.settings.warehouses.delete');
        Route::post('/settings/warehouses/{id}/default', [\App\Http\Controllers\SettingsController::class, 'setDefaultWarehouse'])->name('seller.settings.warehouses.default');
        Route::post('/settings/api-keys', [\App\Http\Controllers\SettingsController::class, 'generateApiKey'])->name('seller.settings.api-keys');
        Route::delete('/settings/api-keys/{id}', [\App\Http\Controllers\SettingsController::class, 'deleteApiKey'])->name('seller.settings.api-keys.delete');
        
        Route::view('/wallet', 'seller.wallet')->name('seller.wallet');
        
        // Cashfree Wallet Recharge
        Route::post('/wallet/recharge', [\App\Http\Controllers\CashfreeController::class, 'initiateRecharge'])->name('seller.wallet.recharge');
        Route::post('/wallet/verify', [\App\Http\Controllers\CashfreeController::class, 'verifyRecharge'])->name('seller.wallet.verify');
        
        // Bookings
        Route::get('/shipments', [\App\Http\Controllers\SellerShipmentController::class, 'index'])->name('seller.shipments.index');
        Route::get('/shipments/{id}/evidence', [\App\Http\Controllers\EvidenceController::class, 'getForShipment'])->name('seller.shipments.evidence');
        Route::post('/shipments/bulk-cancel', [\App\Http\Controllers\SellerShipmentController::class, 'bulkCancel'])->name('seller.shipments.bulk-cancel');
        Route::match(['get', 'post'], '/shipments/bulk-print-slips', [\App\Http\Controllers\SellerShipmentController::class, 'bulkPrintLabels'])->name('seller.shipments.bulk-print');
        Route::post('/shipments/eway-bill', [\App\Http\Controllers\SellerShipmentController::class, 'updateEwayBill'])->name('seller.shipments.eway');
        Route::get('/shipments/{id}/couriers', [\App\Http\Controllers\SellerShipmentController::class, 'getCouriers'])->name('seller.shipments.couriers');
        Route::post('/shipments/{id}/ship-now', [\App\Http\Controllers\SellerShipmentController::class, 'shipNowAction'])->name('seller.shipments.shipNow');
        Route::post('/shipments/{id}/cancel', [\App\Http\Controllers\SellerShipmentController::class, 'cancel'])->name('seller.shipments.cancel');
        Route::get('/book', [\App\Http\Controllers\SellerShipmentController::class, 'create'])->name('seller.book');
        Route::post('/book', [\App\Http\Controllers\SellerShipmentController::class, 'store'])->name('seller.book.post');
        Route::get('/bulk-book', [\App\Http\Controllers\SellerShipmentController::class, 'bulkCreate'])->name('seller.bulk');
        Route::post('/bulk-book', [\App\Http\Controllers\SellerShipmentController::class, 'bulkStore'])->name('seller.bulk.post');
        Route::get('/ndr', [\App\Http\Controllers\SellerNdrController::class, 'index'])->name('seller.ndr');
        Route::post('/ndr/{awb}', [\App\Http\Controllers\SellerNdrController::class, 'action'])->name('seller.ndr.post');
        Route::get('/weight-discrepancies', [\App\Http\Controllers\SellerWeightController::class, 'index'])->name('seller.weight');
        Route::post('/weight-discrepancies/{id}/action', [\App\Http\Controllers\SellerWeightController::class, 'action'])->name('seller.weight.action');
        Route::get('/weight-freeze', [\App\Http\Controllers\SellerWeightFreezeController::class, 'index'])->name('seller.weight.freeze');
        Route::post('/weight-freeze', [\App\Http\Controllers\SellerWeightFreezeController::class, 'store'])->name('seller.weight.freeze.store');
        Route::get('/weight-freeze/export', [\App\Http\Controllers\SellerWeightFreezeController::class, 'export'])->name('seller.weight.freeze.export');
        Route::post('/weight-freeze/import', [\App\Http\Controllers\SellerWeightFreezeController::class, 'import'])->name('seller.weight.freeze.import');
        // Print Label
        Route::get('/shipment/{awb}/label', [\App\Http\Controllers\SellerShipmentController::class, 'printLabel'])->name('seller.label');
        Route::get('/shipment/{awb}/slip', [\App\Http\Controllers\SellerShipmentController::class, 'printLabel'])->name('seller.slip');
        Route::get('/shipment/{awb}/lr', [\App\Http\Controllers\SellerShipmentController::class, 'printLR'])->name('seller.lr');
        Route::get('/shipment/{awb}/invoice', [\App\Http\Controllers\SellerShipmentController::class, 'printInvoice'])->name('seller.invoice');
        Route::get('/api-keys', [\App\Http\Controllers\SellerApiController::class, 'index'])->name('seller.api-keys');
        Route::get('/label-settings', [\App\Http\Controllers\SellerLabelSettingController::class, 'index'])->name('seller.label-settings');
        Route::post('/label-settings', [\App\Http\Controllers\SellerLabelSettingController::class, 'store'])->name('seller.label-settings.store');
        Route::get('/integrations', [\App\Http\Controllers\SellerIntegrationController::class, 'index'])->name('seller.integrations');
        Route::post('/integrations', [\App\Http\Controllers\SellerIntegrationController::class, 'save'])->name('seller.integrations.save');
        Route::post('/integrations/{id}/sync', [\App\Http\Controllers\SellerIntegrationController::class, 'sync'])->name('seller.integrations.sync');
        Route::post('/integrations/{id}/disconnect', [\App\Http\Controllers\SellerIntegrationController::class, 'disconnect'])->name('seller.integrations.disconnect');
        Route::post('/api-keys/generate', [\App\Http\Controllers\SellerApiController::class, 'generate'])->name('seller.api-keys.generate');

        // Seller Monthly Reports & GST Billing
        Route::get('/reports', [\App\Http\Controllers\SellerReportController::class, 'index'])->name('seller.reports.index');
        Route::get('/reports/monthly-csv', [\App\Http\Controllers\SellerReportController::class, 'exportMonthlyCsv'])->name('seller.reports.monthly-csv');
        Route::get('/reports/gst-invoice', [\App\Http\Controllers\SellerReportController::class, 'gstInvoice'])->name('seller.reports.gst-invoice');
    });

        // HUB PORTAL (Requires Franchise Role)
    Route::middleware(['role:franchise'])->prefix('hub')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\HubDashboardController::class, 'index'])->name('hub.dashboard');
        
        // Hub Bookings and Shipments
        Route::get('/shipments', [\App\Http\Controllers\HubShipmentController::class, 'index'])->name('hub.shipments');
                Route::get('/shipments/bulk', [\App\Http\Controllers\HubShipmentController::class, 'bulkCreate'])->name('hub.bulk');
        Route::post('/shipments/bulk', [\App\Http\Controllers\HubShipmentController::class, 'bulkStore'])->name('hub.bulk.store');
        Route::get('/shipments/book', [\App\Http\Controllers\HubShipmentController::class, 'create'])->name('hub.shipments.create');
        Route::post('/shipments/book', [\App\Http\Controllers\HubShipmentController::class, 'store'])->name('hub.shipments.store');
        Route::post('/shipments/bulk-cancel', [\App\Http\Controllers\HubShipmentController::class, 'bulkCancel'])->name('hub.shipments.bulk-cancel');
        Route::match(['get', 'post'], '/shipments/bulk-print-slips', [\App\Http\Controllers\HubShipmentController::class, 'bulkPrintLabels'])->name('hub.shipments.bulk-print');
        Route::post('/shipments/eway-bill', [\App\Http\Controllers\HubShipmentController::class, 'updateEwayBill'])->name('hub.shipments.eway');
        Route::get('/shipments/{id}/couriers', [\App\Http\Controllers\HubShipmentController::class, 'getCouriers'])->name('hub.shipments.couriers');
        Route::post('/shipments/{id}/ship-now', [\App\Http\Controllers\HubShipmentController::class, 'shipNowAction'])->name('hub.shipments.shipNow');
        Route::post('/shipments/{id}/cancel', [\App\Http\Controllers\HubShipmentController::class, 'cancel'])->name('hub.shipments.cancel');
        Route::get('/shipments/label/{awb}', [\App\Http\Controllers\HubShipmentController::class, 'printLabel'])->name('hub.label');
        Route::get('/shipments/slip/{awb}', [\App\Http\Controllers\HubShipmentController::class, 'printLabel'])->name('hub.slip');
        Route::get('/shipments/invoice/{awb}', [\App\Http\Controllers\HubShipmentController::class, 'printInvoice'])->name('hub.invoice');
        Route::get('/shipments/lr/{awb}', [\App\Http\Controllers\HubShipmentController::class, 'printLR'])->name('hub.lr');
        
        // Evidence mock for hub just in case
        Route::get('/shipments/{id}/evidence', [\App\Http\Controllers\EvidenceController::class, 'getForShipment'])->name('hub.shipments.evidence');
        Route::get('/profile', [\App\Http\Controllers\HubDashboardController::class, 'profile'])->name('hub.profile');
        Route::post('/profile', [\App\Http\Controllers\HubDashboardController::class, 'updateProfile'])->name('hub.profile.update');
        Route::get('/warehouses', [\App\Http\Controllers\SettingsController::class, 'getWarehouses'])->name('hub.warehouses.index');
        Route::post('/warehouses', [\App\Http\Controllers\SettingsController::class, 'createWarehouse'])->name('hub.warehouses.store');
        Route::put('/warehouses/{id}', [\App\Http\Controllers\SettingsController::class, 'updateWarehouse'])->name('hub.warehouses.update');
        Route::delete('/warehouses/{id}', [\App\Http\Controllers\SettingsController::class, 'deleteWarehouse'])->name('hub.warehouses.delete');
        Route::post('/warehouses/{id}/default', [\App\Http\Controllers\SettingsController::class, 'setDefaultWarehouse'])->name('hub.warehouses.default');
        
        // KYC Submission
        Route::get('/kyc', function() { return redirect()->route('hub.profile', ['view' => 'kyc']); })->name('hub.kyc.index');
        Route::post('/kyc/submit', [\App\Http\Controllers\KycController::class, 'submit'])->name('hub.kyc.submit');
        Route::post('/scan', [\App\Http\Controllers\HubDashboardController::class, 'scan'])->name('hub.scan');
        
                // Hub Fleet Management
        Route::get("/fleet", [\App\Http\Controllers\HubRiderController::class, "index"])->name("hub.fleet.index");
        Route::post("/fleet", [\App\Http\Controllers\HubRiderController::class, "store"])->name("hub.fleet.store");
        Route::post("/fleet/{id}/update", [\App\Http\Controllers\HubRiderController::class, "update"])->name("hub.fleet.update");
        Route::get('/fleet/{id}/report', [\App\Http\Controllers\HubRiderController::class, 'report'])->name('hub.fleet.report');
        Route::get('/fleet/{id}/runsheet', [\App\Http\Controllers\HubRiderController::class, 'printRunSheet'])->name('hub.fleet.runsheet');
        Route::get('/fleet/{id}/runsheet/csv', [\App\Http\Controllers\HubRiderController::class, 'exportRunSheetCsv'])->name('hub.fleet.runsheet.csv');
        Route::post("/fleet/{id}/delete", [\App\Http\Controllers\HubRiderController::class, "destroy"])->name("hub.fleet.destroy");

        
        // Hub Rider Assignment
        Route::get('/assignments/pickups', [\App\Http\Controllers\HubAssignmentController::class, 'pickups'])->name('hub.assignments.pickups');
        Route::get('/assignments/deliveries', [\App\Http\Controllers\HubAssignmentController::class, 'deliveries'])->name('hub.assignments.deliveries');
        Route::post('/assignments/assign', [\App\Http\Controllers\HubAssignmentController::class, 'assign'])->name('hub.assignments.assign');

        // Hub NDR & RTO
        Route::get('/ndr', [\App\Http\Controllers\HubNdrController::class, 'index'])->name('hub.ndr.index');
        Route::post('/ndr/{id}/action', [\App\Http\Controllers\HubNdrController::class, 'action'])->name('hub.ndr.action');

        // Hub Wallet / Commissions
        Route::get('/wallet', [\App\Http\Controllers\HubWalletController::class, 'index'])->name('hub.wallet.index');
        Route::post('/wallet/recharge', [\App\Http\Controllers\CashfreeController::class, 'initiateRecharge'])->name('hub.wallet.recharge');
        Route::post('/wallet/verify', [\App\Http\Controllers\CashfreeController::class, 'verifyRecharge'])->name('hub.wallet.verify');
        Route::get('/customers', [\App\Http\Controllers\HubCustomerController::class, 'index'])->name('hub.customers.index');
                Route::post('/customers', [\App\Http\Controllers\HubCustomerController::class, 'store'])->name('hub.customers.store');
                Route::get('/sellers', [\App\Http\Controllers\HubSellerController::class, 'index'])->name('hub.sellers.index');
                Route::get('/service-areas', [\App\Http\Controllers\HubServiceAreaController::class, 'index'])->name('hub.service_areas.index');
        Route::get('/reports', [\App\Http\Controllers\HubReportController::class, 'index'])->name('hub.reports.index');
                Route::get('/reports/export', [\App\Http\Controllers\HubReportController::class, 'exportCsv'])->name('hub.reports.export');
        
        // System / Others
        Route::get('/whatsapp', [\App\Http\Controllers\HubSystemController::class, 'whatsapp'])->name('hub.whatsapp.index');
        Route::get('/support', [\App\Http\Controllers\HubSystemController::class, 'support'])->name('hub.support.index');
        Route::get('/notifications', [\App\Http\Controllers\HubSystemController::class, 'notifications'])->name('hub.notifications.index');
        Route::post('/wallet/remit', [\App\Http\Controllers\HubWalletController::class, 'remitCod'])->name('hub.wallet.remit');

        // Hub Bagging & Manifests
        Route::get('/bagging', [\App\Http\Controllers\HubBaggingController::class, 'index'])->name('hub.bagging.index');
        Route::post('/bagging/create', [\App\Http\Controllers\HubBaggingController::class, 'storeBag'])->name('hub.bagging.store');
        Route::get('/bagging/{id}', [\App\Http\Controllers\HubBaggingController::class, 'showBag'])->name('hub.bagging.show');
        Route::post('/bagging/{id}/add', [\App\Http\Controllers\HubBaggingController::class, 'addShipment'])->name('hub.bagging.add_shipment');
        Route::post('/bagging/{bag_id}/remove/{shipment_id}', [\App\Http\Controllers\HubBaggingController::class, 'removeShipment'])->name('hub.bagging.remove_shipment');
        Route::post('/bagging/{id}/seal', [\App\Http\Controllers\HubBaggingController::class, 'sealBag'])->name('hub.bagging.seal');
        Route::post('/bagging/{id}/manifest', [\App\Http\Controllers\HubBaggingController::class, 'createManifest'])->name('hub.bagging.manifest');
        
        Route::get('/manifests/{id}', [\App\Http\Controllers\HubBaggingController::class, 'showManifest'])->name('hub.manifests.show');
        Route::get('/manifests/{id}/print', [\App\Http\Controllers\HubBaggingController::class, 'printManifest'])->name('hub.manifests.print');
        Route::post('/manifests/{id}/dispatch', [\App\Http\Controllers\HubBaggingController::class, 'dispatchManifest'])->name('hub.manifests.dispatch');

        // Hub Integrations
        Route::get('/integrations', [\App\Http\Controllers\SellerIntegrationController::class, 'index'])->name('hub.integrations');
        Route::post('/integrations', [\App\Http\Controllers\SellerIntegrationController::class, 'save'])->name('hub.integrations.save');
        Route::post('/integrations/{id}/sync', [\App\Http\Controllers\SellerIntegrationController::class, 'sync'])->name('hub.integrations.sync');
        Route::post('/integrations/{id}/disconnect', [\App\Http\Controllers\SellerIntegrationController::class, 'disconnect'])->name('hub.integrations.disconnect');
    });

    // RIDER PORTAL (Requires Rider Role)
    Route::middleware(['role:rider,pickup_rider,delivery_rider'])->prefix('rider')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\RiderAppController::class, 'index'])->name('rider.dashboard');
        Route::get('/scan', [\App\Http\Controllers\RiderAppController::class, 'scan'])->name('rider.scan');
        Route::get('/cod', [\App\Http\Controllers\RiderAppController::class, 'cod'])->name('rider.cod');
        Route::get('/profile', [\App\Http\Controllers\RiderAppController::class, 'profile'])->name('rider.profile');
        Route::get('/history', [\App\Http\Controllers\RiderAppController::class, 'history'])->name('rider.history');
        Route::get('/settings', [\App\Http\Controllers\RiderAppController::class, 'settings'])->name('rider.settings');
        Route::post('/location', [\App\Http\Controllers\RiderAppController::class, 'updateLocation'])->name('rider.location.update');
        Route::post('/profile/update', [\App\Http\Controllers\RiderAppController::class, 'updateProfile'])->name('rider.profile.update');
        Route::post('/evidence', [\App\Http\Controllers\RiderAppController::class, 'uploadEvidence'])->name('rider.evidence.upload');
        Route::post('/ndr/{awb}', [\App\Http\Controllers\RiderAppController::class, 'autoNdr'])->name('rider.ndr');
    });
});




Route::get('ndr/resolve/{awb}', [\App\Http\Controllers\PublicContactController::class, 'resolveNdr'])->name('ndr.resolve');
Route::post('ndr/resolve/{awb}', [\App\Http\Controllers\PublicContactController::class, 'submitResolveNdr'])->name('ndr.resolve.submit');












