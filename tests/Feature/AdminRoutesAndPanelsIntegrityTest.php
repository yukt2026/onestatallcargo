<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shipment;
use App\Models\RateCard;
use App\Models\ServiceablePincode;
use App\Models\ApiDoc;
use App\Models\Faq;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminRoutesAndPanelsIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $seller;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'company_name' => 'Fast Delivery Stores',
            'wallet_balance' => 1000.00,
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    public function test_admin_billing_invoices_view_renders_successfully(): void
    {
        Shipment::create([
            'user_id' => $this->seller->id,
            'awb_number' => 'AWB-BILLING-TEST-01',
            'order_id' => 'ORD-BILL-01',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Rohit Kumar',
            'receiver_phone' => '9829011223',
            'delivery_address' => 'Tonk Road',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'pickup_pincode' => '302002',
            'pickup_address' => 'MI Road',
            'weight_kg' => 1.5,
            'invoice_value' => 2500.00,
            'shipping_charge' => 150.00,
            'total_amount' => 150.00,
            'status' => 'Manifested',
            'is_cod' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.billing.invoices'));
        $response->assertStatus(200);
        $response->assertSee('Shipment Commercial Invoices');
        $response->assertSee('AWB-BILLING-TEST-01');
        $response->assertSee('Rohit Kumar');
    }

    public function test_admin_customer_toggle_status_works(): void
    {
        $this->assertEquals('active', $this->customer->status);

        $response = $this->actingAs($this->admin)->post(route('admin.customers.toggle', $this->customer->id));
        $response->assertStatus(302);
        $this->assertEquals('suspended', $this->customer->fresh()->status);

        // Toggle back
        $response2 = $this->actingAs($this->admin)->post(route('admin.customers.toggle', $this->customer->id));
        $response2->assertStatus(302);
        $this->assertEquals('active', $this->customer->fresh()->status);
    }

    public function test_admin_seller_toggle_status_works(): void
    {
        $this->assertEquals('active', $this->seller->status);

        $response = $this->actingAs($this->admin)->post(route('admin.sellers.toggle', $this->seller->id));
        $response->assertStatus(302);
        $this->assertEquals('suspended', $this->seller->fresh()->status);

        // Toggle back
        $response2 = $this->actingAs($this->admin)->post(route('admin.sellers.toggle', $this->seller->id));
        $response2->assertStatus(302);
        $this->assertEquals('active', $this->seller->fresh()->status);
    }

    public function test_admin_ratecard_destroy_works_for_inactive_cards(): void
    {
        $card = RateCard::create([
            'version_name' => 'Legacy Rates 2025',
            'is_active' => false,
            'effective_from' => now()->subYear(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.ratecards.destroy', $card->id));
        $response->assertStatus(302);
        $this->assertDatabaseMissing('rate_cards', ['id' => $card->id]);
    }

    public function test_admin_serviceability_create_show_edit_redirect_to_index(): void
    {
        $pin = ServiceablePincode::create([
            'pincode' => '302005',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'is_active' => true,
        ]);

        $responseCreate = $this->actingAs($this->admin)->get(route('admin.serviceability.create'));
        $responseCreate->assertRedirect(route('admin.serviceability.index'));

        $responseShow = $this->actingAs($this->admin)->get(route('admin.serviceability.show', $pin->id));
        $responseShow->assertRedirect(route('admin.serviceability.index'));

        $responseEdit = $this->actingAs($this->admin)->get(route('admin.serviceability.edit', $pin->id));
        $responseEdit->assertRedirect(route('admin.serviceability.index'));
    }

    public function test_admin_resource_show_methods_redirect_to_edit(): void
    {
        $apiDoc = ApiDoc::create([
            'endpoint_key' => 'track_order',
            'title' => 'Track Order Endpoint',
            'method' => 'GET',
            'path' => '/api/v1/track/{awb}',
            'description' => 'Track order status',
        ]);
        $responseDoc = $this->actingAs($this->admin)->get(route('admin.api_docs.show', $apiDoc->id));
        $responseDoc->assertRedirect(route('admin.api_docs.edit', $apiDoc->id));

        $faq = Faq::create([
            'question' => 'How to track shipment?',
            'answer' => 'Use AWB tracking number.',
        ]);
        $responseFaq = $this->actingAs($this->admin)->get(route('admin.faqs.show', $faq->id));
        $responseFaq->assertRedirect(route('admin.faqs.edit', $faq->id));

        $service = Service::create([
            'title' => 'Express Air Cargo',
            'slug' => 'express-air-cargo',
            'description' => 'Fast delivery via air cargo network',
        ]);
        $responseService = $this->actingAs($this->admin)->get(route('admin.services.show', $service->id));
        $responseService->assertRedirect(route('admin.services.edit', $service->id));

        $testimonial = Testimonial::create([
            'client_name' => 'Anita Sharma',
            'content' => 'Super fast service!',
            'rating' => 5,
        ]);
        $responseTestimonial = $this->actingAs($this->admin)->get(route('admin.testimonials.show', $testimonial->id));
        $responseTestimonial->assertRedirect(route('admin.testimonials.edit', $testimonial->id));

        $setting = Setting::create([
            'key' => 'app_maintenance',
            'value' => 'false',
        ]);
        $responseSetting = $this->actingAs($this->admin)->get(route('admin.settings.show', $setting->id));
        $responseSetting->assertRedirect(route('admin.settings.edit', $setting->id));
    }
}
