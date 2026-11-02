<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shipment;
use App\Models\Kyc;
use App\Models\Franchise;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BulkShipmentSlipPrintDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $otherSeller;
    protected User $hubUser;
    protected User $adminUser;
    protected Franchise $franchise;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'company_name' => 'Seller One Corp',
        ]);

        Kyc::create([
            'user_id' => $this->seller->id,
            'status' => 'approved',
            'document_type' => 'pan',
        ]);

        $this->otherSeller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'company_name' => 'Seller Two Corp',
        ]);

        Kyc::create([
            'user_id' => $this->otherSeller->id,
            'status' => 'approved',
            'document_type' => 'pan',
        ]);

        $this->hubUser = User::factory()->create([
            'role' => 'franchise',
            'status' => 'active',
            'company_name' => 'Metro Hub Logistics',
        ]);

        Kyc::create([
            'user_id' => $this->hubUser->id,
            'status' => 'approved',
            'document_type' => 'pan',
        ]);

        $this->franchise = Franchise::create([
            'user_id' => $this->hubUser->id,
            'company_name' => 'Metro Hub Logistics',
            'owner_name' => 'Metro Owner',
            'phone' => '9829099999',
            'email' => 'metro_hub@onestall.com',
            'address' => 'Station Road',
            'city' => 'Jaipur',
            'district' => 'Jaipur',
            'state' => 'Rajasthan',
            'status' => 'approved',
            'franchise_level' => 'district',
            'serviceable_pincodes' => ['302001', '302002'],
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createShipment(User $user, string $awb, ?int $franchiseId = null): Shipment
    {
        return Shipment::create([
            'user_id' => $user->id,
            'franchise_id' => $franchiseId,
            'awb_number' => $awb,
            'order_id' => 'ORD-' . $awb,
            'shipment_type' => 'B2C',
            'receiver_name' => 'Customer for ' . $awb,
            'receiver_phone' => '9876543210',
            'delivery_address' => 'Sample Address',
            'delivery_city' => 'Delhi',
            'delivery_pincode' => '110001',
            'pickup_pincode' => '110002',
            'weight_kg' => 1.0,
            'invoice_value' => 750.0,
            'shipping_charge' => 80.0,
            'total_amount' => 80.0,
            'status' => 'Manifested',
            'is_cod' => false,
        ]);
    }

    /**
     * Test: Seller can bulk print slips for their own shipments.
     */
    public function test_seller_can_bulk_print_slips(): void
    {
        $s1 = $this->createShipment($this->seller, 'OSC-S-101');
        $s2 = $this->createShipment($this->seller, 'OSC-S-102');

        $response = $this->actingAs($this->seller)->post(route('seller.shipments.bulk-print'), [
            'ids' => "{$s1->id},{$s2->id}",
        ]);

        $response->assertStatus(200);
        $response->assertSee('Bulk Slips: <span style="color: #a5b4fc;">2 Orders</span>', false);
        $response->assertSee('OSC-S-101');
        $response->assertSee('OSC-S-102');
        $response->assertSee('Customer for OSC-S-101');
        $response->assertSee('Customer for OSC-S-102');
    }

    /**
     * Test: Seller cannot bulk print slips of another seller's shipments.
     */
    public function test_seller_cannot_bulk_print_other_sellers_shipments(): void
    {
        $otherShipment = $this->createShipment($this->otherSeller, 'OSC-OTHER-1');

        $response = $this->actingAs($this->seller)->post(route('seller.shipments.bulk-print'), [
            'ids' => (string) $otherShipment->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test: Hub can bulk print slips for owned shipments.
     */
    public function test_hub_can_bulk_print_slips(): void
    {
        $h1 = $this->createShipment($this->seller, 'OSC-H-101', $this->franchise->id);
        $h2 = $this->createShipment($this->seller, 'OSC-H-102', $this->franchise->id);

        $response = $this->actingAs($this->hubUser)->post(route('hub.shipments.bulk-print'), [
            'ids' => "{$h1->id},{$h2->id}",
        ]);

        $response->assertStatus(200);
        $response->assertSee('Bulk Slips: <span style="color: #a5b4fc;">2 Orders</span>', false);
        $response->assertSee('OSC-H-101');
        $response->assertSee('OSC-H-102');
    }

    /**
     * Test: Admin can bulk print slips across all shipments.
     */
    public function test_admin_can_bulk_print_slips(): void
    {
        $s1 = $this->createShipment($this->seller, 'OSC-ADM-1');
        $s2 = $this->createShipment($this->otherSeller, 'OSC-ADM-2');

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipments.bulk-print'), [
            'ids' => "{$s1->id},{$s2->id}",
        ]);

        $response->assertStatus(200);
        $response->assertSee('Bulk Slips: <span style="color: #a5b4fc;">2 Orders</span>', false);
        $response->assertSee('OSC-ADM-1');
        $response->assertSee('OSC-ADM-2');
    }

    /**
     * Test: Empty selection redirects back with error.
     */
    public function test_empty_selection_redirects_with_error(): void
    {
        $response = $this->actingAs($this->seller)->post(route('seller.shipments.bulk-print'), [
            'ids' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Please select at least one shipment to print slips.');
    }
}
