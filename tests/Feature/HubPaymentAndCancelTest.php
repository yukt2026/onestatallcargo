<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Franchise;
use App\Models\Shipment;
use App\Models\Kyc;
use App\Models\WalletTransaction;
use App\Services\AwbGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HubPaymentAndCancelTest extends TestCase
{
    use RefreshDatabase;

    protected User $hubUser;
    protected User $sellerUser;
    protected Franchise $franchise;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hubUser = User::factory()->create([
            'role' => 'franchise',
            'wallet_balance' => 500.00,
        ]);

        Kyc::create([
            'user_id' => $this->hubUser->id,
            'status' => 'approved',
            'document_type' => 'aadhaar',
            'document_number' => '123456789012',
        ]);

        $this->sellerUser = User::factory()->create([
            'role' => 'seller',
            'wallet_balance' => 50000.00,
        ]);

        Kyc::create([
            'user_id' => $this->sellerUser->id,
            'status' => 'approved',
            'document_type' => 'aadhaar',
            'document_number' => '987654321098',
        ]);

        $this->franchise = Franchise::create([
            'user_id' => $this->hubUser->id,
            'company_name' => 'Jaipur Hub Logistics',
            'owner_name' => 'Jaipur Manager',
            'phone' => '9876543210',
            'email' => 'hubtest@onestall.com',
            'address' => 'MI Road, Jaipur',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'status' => 'approved',
            'franchise_level' => 'district',
            'serviceable_pincodes' => json_encode(['302001', '302002']),
        ]);

        \App\Models\ServiceablePincode::create([
            'pincode' => '302001',
            'franchise_id' => $this->franchise->id,
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'is_active' => true,
        ]);
        \App\Models\ServiceablePincode::create([
            'pincode' => '302002',
            'franchise_id' => $this->franchise->id,
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'is_active' => true,
        ]);
    }

    /**
     * Test: AWB number is generated in clear AWB-1234... format.
     */
    public function test_awb_number_generated_in_clear_awb_prefix_format(): void
    {
        $service = app(AwbGeneratorService::class);
        $awb = $service->generateUniqueAwb();

        $this->assertNotEmpty($awb);
        $this->assertStringStartsWith('AWB-', $awb);
        $numericPart = substr($awb, 4);
        $this->assertTrue(ctype_digit($numericPart), "AWB numeric part [{$numericPart}] should be digits.");
    }

    /**
     * Test: Hub single shipment creation generates AWB-1234... format and deducts from wallet.
     */
    public function test_hub_booking_generates_numeric_awb_and_deducts_wallet(): void
    {
        $initialBalance = $this->hubUser->wallet_balance;

        $response = $this->actingAs($this->hubUser)
            ->post(route('hub.shipments.store'), [
                'order_id' => 'ORD-TEST-NUM-01',
                'shipment_type' => 'B2C',
                'receiver_name' => 'Rahul Sharma',
                'receiver_phone' => '9876543210',
                'delivery_address' => 'Plot 42, Malviya Nagar',
                'delivery_city' => 'Jaipur',
                'delivery_state' => 'Rajasthan',
                'delivery_pincode' => '302001',
                'pickup_pincode' => '302002',
                'pickup_address' => 'Warehouse A, Sitapura',
                'pickup_city' => 'Jaipur',
                'weight_kg' => 1.0,
                'length_cm' => 10,
                'width_cm' => 10,
                'height_cm' => 10,
                'invoice_value' => 500,
                'payment_mode' => 'Prepaid',
                'ship_now' => 1,
            ]);

        $response->assertStatus(302);
        $shipment = Shipment::where('order_id', 'ORD-TEST-NUM-01')->first();
        $this->assertNotNull($shipment);

        // AWB must be in AWB-12345678 format
        $this->assertStringStartsWith('AWB-', $shipment->awb_number);
        $this->assertTrue(ctype_digit(substr($shipment->awb_number, 4)), "AWB [{$shipment->awb_number}] must have digits after AWB-.");

        // Wallet was debited
        $newBalance = $this->hubUser->fresh()->wallet_balance;
        $this->assertLessThan($initialBalance, $newBalance);
        $this->assertEquals($initialBalance - $shipment->total_amount, $newBalance);

        // Wallet transaction exists
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->hubUser->id,
            'type' => 'debit',
            'reference_id' => $shipment->awb_number,
        ]);
    }

    /**
     * Test: Ship Now on uncharged shipment deducts full freight amount.
     */
    public function test_ship_now_deducts_full_freight_if_not_previously_debited(): void
    {
        $shipment = Shipment::create([
            'user_id' => $this->hubUser->id,
            'awb_number' => app(AwbGeneratorService::class)->generateUniqueAwb(),
            'order_id' => 'ORD-UNCHARGED-01',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Mohit Verma',
            'receiver_phone' => '9829000000',
            'delivery_address' => 'Vaishali Nagar',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'weight_kg' => 1.0,
            'invoice_value' => 500.00,
            'shipping_charge' => 0,
            'total_amount' => 0,
            'status' => 'Manifested',
        ]);

        $initialBalance = $this->hubUser->wallet_balance;

        $response = $this->actingAs($this->hubUser)
            ->post(route('hub.shipments.shipNow', $shipment->id), [
                'courier_partner' => 'OneStall Surface',
                'shipping_charge' => 85.00,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertEquals(
            $initialBalance - 85.00,
            $this->hubUser->fresh()->wallet_balance
        );

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->hubUser->id,
            'type' => 'debit',
            'amount' => 85.00,
            'reference_id' => $shipment->awb_number,
        ]);

        $shipment->refresh();
        $this->assertEquals('Pickup Scheduled', $shipment->status);
        $this->assertEquals(85.00, (float)$shipment->shipping_charge);
    }

    /**
     * Test: Insufficient wallet balance prevents shipping and returns error.
     */
    public function test_ship_now_fails_if_insufficient_wallet_balance(): void
    {
        $this->hubUser->update(['wallet_balance' => 20.00]);

        $shipment = Shipment::create([
            'user_id' => $this->hubUser->id,
            'awb_number' => app(AwbGeneratorService::class)->generateUniqueAwb(),
            'order_id' => 'ORD-LOW-BAL-01',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Suman Roy',
            'receiver_phone' => '9829000001',
            'delivery_address' => 'C Scheme',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'weight_kg' => 1.0,
            'invoice_value' => 500.00,
            'shipping_charge' => 0,
            'total_amount' => 0,
            'status' => 'Manifested',
        ]);

        $response = $this->actingAs($this->hubUser)
            ->post(route('hub.shipments.shipNow', $shipment->id), [
                'courier_partner' => 'OneStall Express',
                'shipping_charge' => 90.00,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        // Balance remains unchanged
        $this->assertEquals(20.00, $this->hubUser->fresh()->wallet_balance);
        $shipment->refresh();
        $this->assertEquals('Manifested', $shipment->status);
    }

    /**
     * Test: Cancelling an order in 'Manifested' status refunds wallet and creates transactions.
     */
    public function test_hub_can_cancel_manifested_order_with_wallet_refund(): void
    {
        $awb = app(AwbGeneratorService::class)->generateUniqueAwb();
        $shipment = Shipment::create([
            'user_id' => $this->hubUser->id,
            'awb_number' => $awb,
            'order_id' => 'ORD-CANCEL-01',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Karan Patel',
            'receiver_phone' => '9829000002',
            'delivery_address' => 'Tonk Road',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'weight_kg' => 1.0,
            'invoice_value' => 500.00,
            'shipping_charge' => 70.00,
            'total_amount' => 70.00,
            'status' => 'Manifested',
        ]);

        WalletTransaction::create([
            'user_id' => $this->hubUser->id,
            'amount' => 70.00,
            'type' => 'debit',
            'balance_after' => 430.00,
            'reference_id' => $awb,
            'description' => 'Freight deduction',
            'status' => 'success',
        ]);
        $this->hubUser->update(['wallet_balance' => 430.00]);

        $response = $this->actingAs($this->hubUser)
            ->post(route('hub.shipments.cancel', $shipment->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals('cancelled', strtolower($shipment->status));

        // Wallet was credited
        $this->assertEquals(500.00, $this->hubUser->fresh()->wallet_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->hubUser->id,
            'type' => 'credit',
            'amount' => 70.00,
            'reference_id' => $awb,
        ]);

        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'status' => 'Cancelled',
        ]);
    }

    /**
     * Test: Cancelling an order after 'Pickup Scheduled' works and refunds wallet.
     */
    public function test_hub_can_cancel_order_after_pickup_scheduled(): void
    {
        $awb = app(AwbGeneratorService::class)->generateUniqueAwb();
        $shipment = Shipment::create([
            'user_id' => $this->hubUser->id,
            'awb_number' => $awb,
            'order_id' => 'ORD-CANCEL-SCHEDULED-02',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Nikhil Meena',
            'receiver_phone' => '9829000003',
            'delivery_address' => 'Ajmer Road',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'weight_kg' => 1.0,
            'invoice_value' => 500.00,
            'shipping_charge' => 95.00,
            'total_amount' => 95.00,
            'status' => 'Pickup Scheduled',
        ]);

        WalletTransaction::create([
            'user_id' => $this->hubUser->id,
            'amount' => 95.00,
            'type' => 'debit',
            'balance_after' => 405.00,
            'reference_id' => $awb,
            'description' => 'Freight deduction',
            'status' => 'success',
        ]);
        $this->hubUser->update(['wallet_balance' => 405.00]);

        $response = $this->actingAs($this->hubUser)
            ->post(route('hub.shipments.cancel', $shipment->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals('cancelled', strtolower($shipment->status));
        $this->assertEquals(500.00, $this->hubUser->fresh()->wallet_balance);
    }

    /**
     * Test: Hub booking with custom pickup details (Sagar Restaurant / 8789796492 / 583135)
     * accurately stores custom pickup, saves warehouse, and renders on invoice/label/lr
     * without falling back to hub user shop credentials.
     */
    public function test_hub_booking_with_custom_pickup_details_stores_and_displays_accurately(): void
    {
        // Hub owner shop credentials
        $this->hubUser->update([
            'company_name' => '241124 sahabad',
            'phone' => '9198208972',
            'city' => 'Shahabad',
            'state' => 'Haryana',
            'pincode' => '136135',
        ]);

        // Serviceable delivery destination
        \App\Models\ServiceablePincode::create([
            'pincode' => '638106',
            'franchise_id' => $this->franchise->id,
            'city' => 'Mulanur',
            'state' => 'Tamil Nadu',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hubUser)
            ->post(route('hub.shipments.store'), [
                'order_id' => 'ORD-SAGAR-01',
                'shipment_type' => 'B2C',
                'receiver_name' => 'Anand',
                'receiver_phone' => '9025970866',
                'delivery_address' => 'D.k fast food Esswaran Koil Rd, Mulanur',
                'delivery_city' => 'Mulanur',
                'delivery_state' => 'Tamil Nadu',
                'delivery_pincode' => '638106',
                'pickup_name' => 'Sagar Restaurant',
                'pickup_phone' => '8789796492',
                'pickup_address' => 'Sagar Restaurant Gudedekote Road, Kuldigi',
                'pickup_city' => 'Kuldigi',
                'pickup_state' => 'Karnataka',
                'pickup_pincode' => '583135',
                'save_to_warehouses' => '1',
                'weight_kg' => 6.0,
                'length_cm' => 35,
                'width_cm' => 35,
                'height_cm' => 20,
                'invoice_value' => 12550.00,
                'payment_mode' => 'Prepaid',
                'ship_now' => 1,
            ]);

        $response->assertStatus(302);
        $shipment = Shipment::where('order_id', 'ORD-SAGAR-01')->first();
        $this->assertNotNull($shipment);

        // 1. Shipment pickup fields
        $this->assertEquals('Sagar Restaurant', $shipment->pickup_name);
        $this->assertEquals('8789796492', $shipment->pickup_phone);
        $this->assertEquals('583135', $shipment->pickup_pincode);
        $this->assertEquals('Kuldigi', $shipment->pickup_city);
        $this->assertEquals('Karnataka', $shipment->pickup_state);
        $this->assertEquals('Sagar Restaurant Gudedekote Road, Kuldigi', $shipment->pickup_address);

        // 2. Warehouse auto-created
        $this->assertDatabaseHas('warehouses', [
            'user_id' => $this->hubUser->id,
            'name' => 'Sagar Restaurant',
            'phone' => '8789796492',
            'pincode' => '583135',
            'city' => 'Kuldigi',
            'state' => 'Karnataka',
        ]);

        // 3. Invoice displays custom pickup name & phone, not hub user shop credentials
        $invoiceRes = $this->actingAs($this->hubUser)->get(route('hub.invoice', $shipment->awb_number));
        $invoiceRes->assertStatus(200);
        $invoiceRes->assertSee('Sagar Restaurant');
        $invoiceRes->assertSee('8789796492');
        $invoiceRes->assertSee('583135');
        $invoiceRes->assertDontSee('9198208972');

        // 4. Label displays custom pickup in Return Address & Support Details
        $labelRes = $this->actingAs($this->hubUser)->get(route('hub.label', $shipment->awb_number));
        $labelRes->assertStatus(200);
        $labelRes->assertSee('Sagar Restaurant');
        $labelRes->assertSee('583135');
        $labelRes->assertSee('Anand');
        $labelRes->assertSee('9025970866');
        $labelRes->assertSee('8789796492');

        // 5. LR (Consignment Note) displays custom pickup consignor
        $lrRes = $this->actingAs($this->hubUser)->get(route('hub.lr', $shipment->awb_number));
        $lrRes->assertStatus(200);
        $lrRes->assertSee('Sagar Restaurant');
        $lrRes->assertSee('8789796492');
        $lrRes->assertSee('583135');
    }

    /**
     * Test: B2B Cargo booking stores vehicle type and E-Way bill number,
     * and renders them properly on the LR (Consignment Note) and Tax Invoice.
     */
    public function test_b2b_cargo_booking_stores_vehicle_type_and_eway_bill_and_renders_on_lr_and_invoice(): void
    {
        $this->hubUser->update(['wallet_balance' => 50000.00]);

        $response = $this->actingAs($this->hubUser)
            ->post(route('hub.shipments.store'), [
                'order_id' => 'ORD-B2B-HEAVY-01',
                'shipment_type' => 'B2B',
                'vehicle_type' => '14ft Eicher',
                'eway_bill_number' => '241122334455',
                'receiver_name' => 'Tata Steel Fabrication',
                'receiver_phone' => '9829012345',
                'delivery_address' => 'Industrial Area, Phase 2',
                'delivery_city' => 'Jaipur',
                'delivery_state' => 'Rajasthan',
                'delivery_pincode' => '302001',
                'pickup_pincode' => '302002',
                'pickup_address' => 'Sitapura Steel Yard',
                'pickup_city' => 'Jaipur',
                'pickup_state' => 'Rajasthan',
                'weight_kg' => 150.0,
                'length_cm' => 100,
                'width_cm' => 100,
                'height_cm' => 80,
                'invoice_value' => 75000.00,
                'payment_mode' => 'Prepaid',
                'ship_now' => 1,
            ]);

        $response->assertStatus(302);
        $shipment = Shipment::where('order_id', 'ORD-B2B-HEAVY-01')->first();
        $this->assertNotNull($shipment);

        // 1. B2B fields verified in database
        $this->assertEquals('B2B', $shipment->shipment_type);
        $this->assertEquals('14ft Eicher', $shipment->vehicle_type);
        $this->assertEquals('241122334455', $shipment->eway_bill_number);

        // 2. LR displays vehicle type and E-Way bill number
        $lrRes = $this->actingAs($this->hubUser)->get(route('hub.lr', $shipment->awb_number));
        $lrRes->assertStatus(200);
        $lrRes->assertSee('14ft Eicher');
        $lrRes->assertSee('241122334455');
        $lrRes->assertSee('General Cargo (B2B)');

        // 3. Invoice displays E-Way bill number
        $invoiceRes = $this->actingAs($this->hubUser)->get(route('hub.invoice', $shipment->awb_number));
        $invoiceRes->assertStatus(200);
        $invoiceRes->assertSee('241122334455');
    }

    /**
     * Test: Seller booking with custom pickup details stores properly, saves warehouse,
     * and displays on seller invoice, label, and lr.
     */
    public function test_seller_booking_with_custom_pickup_details_stores_and_displays_accurately(): void
    {
        $this->sellerUser->update([
            'company_name' => 'Original Seller Store',
            'phone' => '9999999999',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.book.post'), [
                'order_id' => 'ORD-SELLER-SAGAR-01',
                'shipment_type' => 'B2C',
                'receiver_name' => 'Anand',
                'receiver_phone' => '9025970866',
                'delivery_address' => 'D.k fast food Esswaran Koil Rd, Mulanur',
                'delivery_city' => 'Mulanur',
                'delivery_state' => 'Tamil Nadu',
                'delivery_pincode' => '302001',
                'pickup_name' => 'Sagar Restaurant',
                'pickup_phone' => '8789796492',
                'pickup_address' => 'Sagar Restaurant Gudedekote Road, Kuldigi',
                'pickup_city' => 'Kuldigi',
                'pickup_state' => 'Karnataka',
                'pickup_pincode' => '583135',
                'save_to_warehouses' => '1',
                'weight_kg' => 6.0,
                'length_cm' => 35,
                'width_cm' => 35,
                'height_cm' => 20,
                'invoice_value' => 12550.00,
                'payment_mode' => 'Prepaid',
                'ship_now' => 1,
            ]);

        $response->assertStatus(302);
        $shipment = Shipment::where('order_id', 'ORD-SELLER-SAGAR-01')->first();
        $this->assertNotNull($shipment);

        $this->assertEquals('Sagar Restaurant', $shipment->pickup_name);
        $this->assertEquals('8789796492', $shipment->pickup_phone);
        $this->assertEquals('583135', $shipment->pickup_pincode);
        $this->assertEquals('Kuldigi', $shipment->pickup_city);
        $this->assertEquals('Karnataka', $shipment->pickup_state);

        // Warehouse auto-created for seller
        $this->assertDatabaseHas('warehouses', [
            'user_id' => $this->sellerUser->id,
            'name' => 'Sagar Restaurant',
            'phone' => '8789796492',
            'pincode' => '583135',
        ]);

        // Invoice
        $invoiceRes = $this->actingAs($this->sellerUser)->get(route('seller.invoice', $shipment->awb_number));
        $invoiceRes->assertStatus(200);
        $invoiceRes->assertSee('Sagar Restaurant');
        $invoiceRes->assertSee('8789796492');
        $invoiceRes->assertSee('583135');

        // Label
        $labelRes = $this->actingAs($this->sellerUser)->get(route('seller.label', $shipment->awb_number));
        $labelRes->assertStatus(200);
        $labelRes->assertSee('Sagar Restaurant');
        $labelRes->assertSee('583135');
        $labelRes->assertSee('Anand');
        $labelRes->assertSee('9025970866');

        // LR
        $lrRes = $this->actingAs($this->sellerUser)->get(route('seller.lr', $shipment->awb_number));
        $lrRes->assertStatus(200);
        $lrRes->assertSee('Sagar Restaurant');
        $lrRes->assertSee('8789796492');
    }

    /**
     * Test: Seller can cancel shipment and receive an accurate wallet refund.
     */
    public function test_seller_can_cancel_shipment_with_wallet_refund(): void
    {
        $awb = app(AwbGeneratorService::class)->generateUniqueAwb();
        $shipment = Shipment::create([
            'user_id' => $this->sellerUser->id,
            'awb_number' => $awb,
            'order_id' => 'ORD-SELLER-CANCEL-01',
            'shipment_type' => 'B2C',
            'receiver_name' => 'Ramesh',
            'receiver_phone' => '9829011111',
            'delivery_address' => 'Tonk Road',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'weight_kg' => 2.0,
            'invoice_value' => 1200.00,
            'shipping_charge' => 120.00,
            'total_amount' => 120.00,
            'status' => 'Manifested',
        ]);

        WalletTransaction::create([
            'user_id' => $this->sellerUser->id,
            'amount' => 120.00,
            'type' => 'debit',
            'balance_after' => 49880.00,
            'reference_id' => $awb,
            'description' => 'Shipment Booking Deduction',
            'status' => 'success',
        ]);
        $this->sellerUser->update(['wallet_balance' => 49880.00]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.shipments.cancel', $shipment->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $shipment->refresh();
        $this->assertEquals('cancelled', strtolower($shipment->status));
        $this->assertEquals(50000.00, $this->sellerUser->fresh()->wallet_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->sellerUser->id,
            'type' => 'credit',
            'amount' => 120.00,
            'reference_id' => $awb,
        ]);
    }
}
