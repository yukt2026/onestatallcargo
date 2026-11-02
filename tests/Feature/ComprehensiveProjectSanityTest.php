<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shipment;
use App\Models\Kyc;
use App\Models\Franchise;
use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComprehensiveProjectSanityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $seller;
    protected User $hubUser;
    protected Franchise $franchise;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'name' => 'Super Admin',
            'email' => 'admin_sanity@onestall.com',
        ]);

        // 2. Create Seller with KYC
        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'name' => 'Sanity Seller',
            'company_name' => 'Sanity Seller Corp',
            'email' => 'seller_sanity@onestall.com',
            'wallet_balance' => 10000.00,
        ]);

        Kyc::create([
            'user_id' => $this->seller->id,
            'status' => 'approved',
            'document_type' => 'pan',
        ]);

        // 3. Create Hub User with Franchise
        $this->hubUser = User::factory()->create([
            'role' => 'franchise',
            'status' => 'active',
            'name' => 'Sanity Hub Manager',
            'company_name' => 'Sanity Hub Logistics',
            'email' => 'hub_sanity@onestall.com',
            'wallet_balance' => 5000.00,
        ]);

        Kyc::create([
            'user_id' => $this->hubUser->id,
            'status' => 'approved',
            'document_type' => 'pan',
        ]);

        $this->franchise = Franchise::create([
            'user_id' => $this->hubUser->id,
            'company_name' => 'Sanity Hub Logistics',
            'owner_name' => 'Hub Owner',
            'phone' => '9829099888',
            'email' => 'hub_sanity_franchise@onestall.com',
            'address' => 'Sanity Hub Road',
            'city' => 'Jaipur',
            'district' => 'Jaipur',
            'state' => 'Rajasthan',
            'status' => 'approved',
            'franchise_level' => 'district',
            'serviceable_pincodes' => ['302001', '302002'],
        ]);

        // 4. Create Active Courier
        Courier::firstOrCreate(['name' => 'OneStall Cargo'], ['is_active' => true]);
        Courier::firstOrCreate(['name' => 'Delhivery'], ['is_active' => true]);

        // 5. Seed a Sample Shipment
        Shipment::create([
            'user_id' => $this->seller->id,
            'franchise_id' => $this->franchise->id,
            'awb_number' => 'OSC-SANITY-001',
            'order_id' => 'ORD-SANITY-001',
            'shipment_type' => 'B2C',
            'receiver_name' => 'John Sanity',
            'receiver_phone' => '9876543210',
            'delivery_address' => '123 Test Street',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'pickup_pincode' => '110001',
            'weight_kg' => 0.75,
            'invoice_value' => 1200.0,
            'shipping_charge' => 85.0,
            'total_amount' => 85.0,
            'status' => 'Manifested',
            'courier_partner' => 'OneStall Cargo',
            'is_cod' => false,
        ]);
    }

    /**
     * Test 1: Public frontend routes render with 200 OK.
     */
    public function test_public_pages_render_successfully(): void
    {
        $routes = [
            '/',
            '/about',
            '/contact',
            '/services',
            '/pricing',
            '/terms',
            '/privacy',
            '/track',
            '/franchise',
            '/solutions/b2c-shipping',
            '/solutions/b2b-cargo',
            '/login',
            '/register',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $this->assertContains($response->getStatusCode(), [200, 302], "Failed asserting route '{$route}' returned 200 or 302. Got: " . $response->getStatusCode());
        }
    }

    /**
     * Test 2: Seller portal core GET routes render with 200 OK.
     */
    public function test_seller_portal_pages_render_successfully(): void
    {
        $namedRoutes = [
            'seller.dashboard',
            'seller.shipments.index',
            'seller.book',
            'seller.bulk',
            'seller.ndr',
            'seller.weight',
            'seller.weight.freeze',
            'seller.reports.index',
            'seller.label-settings',
            'seller.integrations',
            'seller.settings',
            'seller.wallet',
        ];

        foreach ($namedRoutes as $name) {
            $response = $this->actingAs($this->seller)->get(route($name));
            $this->assertEquals(200, $response->getStatusCode(), "Seller route '{$name}' failed with status: " . $response->getStatusCode());
        }
    }

    /**
     * Test 3: Hub / Franchise portal core GET routes render with 200 OK.
     */
    public function test_hub_portal_pages_render_successfully(): void
    {
        $namedRoutes = [
            'hub.dashboard',
            'hub.shipments',
            'hub.shipments.create',
            'hub.bulk',
            'hub.bagging.index',
            'hub.fleet.index',
            'hub.ndr.index',
            'hub.wallet.index',
            'hub.reports.index',
            'hub.profile',
        ];

        foreach ($namedRoutes as $name) {
            $response = $this->actingAs($this->hubUser)->get(route($name));
            $this->assertEquals(200, $response->getStatusCode(), "Hub route '{$name}' failed with status: " . $response->getStatusCode());
        }
    }

    /**
     * Test 4: Admin portal core GET routes render with 200 OK.
     */
    public function test_admin_portal_pages_render_successfully(): void
    {
        $namedRoutes = [
            'admin.dashboard',
            'admin.shipments.index',
            'admin.shipments.create',
            'admin.hubs.index',
            'admin.sellers.index',
            'admin.customers.index',
            'admin.billing.index',
            'admin.billing.invoices',
            'admin.ratecards.index',
            'admin.serviceability.index',
            'admin.weight',
            'admin.weight.freeze',
            'admin.kyc.index',
            'admin.ndr.index',
            'admin.contacts.index',
            'admin.faqs.index',
            'admin.testimonials.index',
            'admin.settings.index',
            'admin.roles.index',
            'admin.riders.index',
        ];

        foreach ($namedRoutes as $name) {
            $response = $this->actingAs($this->admin)->get(route($name));
            $this->assertEquals(200, $response->getStatusCode(), "Admin route '{$name}' failed with status: " . $response->getStatusCode());
        }
    }

    /**
     * Test 5: AWB print actions (Slip, Invoice, LR) render for all three roles.
     */
    public function test_print_actions_render_for_seller_hub_and_admin(): void
    {
        $awb = 'OSC-SANITY-001';

        // 1. Seller prints
        $res = $this->actingAs($this->seller)->get(route('seller.label', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Seller label failed");

        $res = $this->actingAs($this->seller)->get(route('seller.invoice', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Seller invoice failed");

        $res = $this->actingAs($this->seller)->get(route('seller.lr', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Seller LR failed");

        // 2. Hub prints
        $res = $this->actingAs($this->hubUser)->get(route('hub.label', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Hub label failed");

        $res = $this->actingAs($this->hubUser)->get(route('hub.invoice', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Hub invoice failed");

        $res = $this->actingAs($this->hubUser)->get(route('hub.lr', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Hub LR failed");

        // 3. Admin prints
        $res = $this->actingAs($this->admin)->get(route('admin.shipments.label', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Admin label failed");

        $res = $this->actingAs($this->admin)->get(route('admin.shipments.invoice', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Admin invoice failed");

        $res = $this->actingAs($this->admin)->get(route('admin.shipments.lr', $awb));
        $this->assertEquals(200, $res->getStatusCode(), "Admin LR failed");
    }
}
