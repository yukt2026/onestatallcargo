<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Franchise;
use App\Models\Kyc;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FranchiseShipmentSlipDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $sellerUser;
    private User $stateManager;
    private Franchise $stateFranchise;
    private User $district1Manager;
    private Franchise $district1Franchise;
    private User $district2Manager;
    private Franchise $district2Franchise;
    private User $otherStateManager;
    private Franchise $otherStateFranchise;
    private Shipment $shipmentDistrict1;
    private Shipment $shipmentDistrict2;
    private Shipment $shipmentOtherState;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin
        $this->adminUser = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // 2. Seller
        $this->sellerUser = User::factory()->create(['role' => 'seller', 'status' => 'active']);
        Kyc::create(['user_id' => $this->sellerUser->id, 'status' => 'approved', 'document_type' => 'pan']);

        // 3. State Franchise (Rajasthan)
        $this->stateManager = User::factory()->create(['role' => 'franchise', 'status' => 'active']);
        Kyc::create(['user_id' => $this->stateManager->id, 'status' => 'approved', 'document_type' => 'pan']);
        $this->stateFranchise = Franchise::create([
            'user_id' => $this->stateManager->id,
            'company_name' => 'Rajasthan State Apex',
            'owner_name' => 'State Manager',
            'phone' => '9829011111',
            'email' => 'state_apex@onestall.com',
            'address' => 'Jaipur HQ',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'status' => 'approved',
            'franchise_level' => 'state',
        ]);

        // 4. District 1 Franchise (under Rajasthan)
        $this->district1Manager = User::factory()->create(['role' => 'franchise', 'status' => 'active']);
        Kyc::create(['user_id' => $this->district1Manager->id, 'status' => 'approved', 'document_type' => 'pan']);
        $this->district1Franchise = Franchise::create([
            'user_id' => $this->district1Manager->id,
            'parent_franchise_id' => $this->stateFranchise->id,
            'company_name' => 'Jaipur District Hub',
            'owner_name' => 'Jaipur Lead',
            'phone' => '9829022222',
            'email' => 'jaipur_hub@onestall.com',
            'address' => 'MI Road',
            'city' => 'Jaipur',
            'district' => 'Jaipur',
            'state' => 'Rajasthan',
            'status' => 'approved',
            'franchise_level' => 'district',
            'serviceable_pincodes' => ['302001', '302002'],
        ]);

        // 5. District 2 Franchise (under Rajasthan)
        $this->district2Manager = User::factory()->create(['role' => 'franchise', 'status' => 'active']);
        Kyc::create(['user_id' => $this->district2Manager->id, 'status' => 'approved', 'document_type' => 'pan']);
        $this->district2Franchise = Franchise::create([
            'user_id' => $this->district2Manager->id,
            'parent_franchise_id' => $this->stateFranchise->id,
            'company_name' => 'Kota District Hub',
            'owner_name' => 'Kota Lead',
            'phone' => '9829033333',
            'email' => 'kota_hub@onestall.com',
            'address' => 'Aerodrome Circle',
            'city' => 'Kota',
            'district' => 'Kota',
            'state' => 'Rajasthan',
            'status' => 'approved',
            'franchise_level' => 'district',
            'serviceable_pincodes' => ['324005'],
        ]);

        // 6. Other State Franchise (Maharashtra)
        $this->otherStateManager = User::factory()->create(['role' => 'franchise', 'status' => 'active']);
        Kyc::create(['user_id' => $this->otherStateManager->id, 'status' => 'approved', 'document_type' => 'pan']);
        $this->otherStateFranchise = Franchise::create([
            'user_id' => $this->otherStateManager->id,
            'company_name' => 'Maharashtra State Apex',
            'owner_name' => 'MH Manager',
            'phone' => '9829044444',
            'email' => 'mh_state@onestall.com',
            'address' => 'Nariman Point',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'status' => 'approved',
            'franchise_level' => 'state',
        ]);

        // 7. Shipments
        $this->shipmentDistrict1 = Shipment::create([
            'awb_number' => 'OSCJAI001',
            'user_id' => $this->sellerUser->id,
            'franchise_id' => $this->district1Franchise->id,
            'state_franchise_id' => $this->stateFranchise->id,
            'receiver_name' => 'Jaipur Customer',
            'receiver_phone' => '9876543210',
            'delivery_address' => 'Bapu Nagar',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'delivery_state' => 'Rajasthan',
            'pickup_address' => 'Seller Warehouse',
            'pickup_city' => 'Delhi',
            'pickup_pincode' => '110001',
            'weight_kg' => 1.5,
            'invoice_value' => 1200.00,
            'total_amount' => 120.00,
            'shipping_charge' => 120.00,
            'status' => 'Manifested',
            'shipment_type' => 'B2C',
            'courier_partner' => 'OneStall Cargo',
        ]);

        $this->shipmentDistrict2 = Shipment::create([
            'awb_number' => 'OSCKOT002',
            'user_id' => $this->sellerUser->id,
            'franchise_id' => $this->district2Franchise->id,
            'state_franchise_id' => $this->stateFranchise->id,
            'receiver_name' => 'Kota Customer',
            'receiver_phone' => '9876543211',
            'delivery_address' => 'Talwandi',
            'delivery_city' => 'Kota',
            'delivery_pincode' => '324005',
            'delivery_state' => 'Rajasthan',
            'pickup_address' => 'Seller Warehouse',
            'pickup_city' => 'Delhi',
            'pickup_pincode' => '110001',
            'weight_kg' => 2.0,
            'invoice_value' => 2500.00,
            'total_amount' => 180.00,
            'shipping_charge' => 180.00,
            'status' => 'In Transit',
            'shipment_type' => 'B2C',
            'courier_partner' => 'OneStall Cargo',
        ]);

        $this->shipmentOtherState = Shipment::create([
            'awb_number' => 'OSCMUM003',
            'user_id' => $this->sellerUser->id,
            'state_franchise_id' => $this->otherStateFranchise->id,
            'receiver_name' => 'Mumbai Customer',
            'receiver_phone' => '9876543212',
            'delivery_address' => 'Andheri East',
            'delivery_city' => 'Mumbai',
            'delivery_pincode' => '400069',
            'delivery_state' => 'Maharashtra',
            'pickup_address' => 'Seller Warehouse',
            'pickup_city' => 'Pune',
            'pickup_pincode' => '411001',
            'weight_kg' => 1.0,
            'invoice_value' => 800.00,
            'total_amount' => 95.00,
            'shipping_charge' => 95.00,
            'status' => 'Manifested',
            'shipment_type' => 'B2C',
            'courier_partner' => 'OneStall Cargo',
        ]);
    }

    public function test_unauthenticated_request_to_slip_download_redirects_to_login(): void
    {
        $response = $this->get(route('hub.label', $this->shipmentDistrict1->awb_number));
        $response->assertRedirect('/login');

        $responseSlip = $this->get(route('hub.slip', $this->shipmentDistrict1->awb_number));
        $responseSlip->assertRedirect('/login');
    }

    public function test_district_franchise_can_download_assigned_shipment_slip(): void
    {
        // Authenticate District 1 manager
        $response = $this->actingAs($this->district1Manager)
            ->get(route('hub.label', $this->shipmentDistrict1->awb_number));

        $response->assertStatus(200);
        $response->assertSee('OSCJAI001');
        $response->assertSee('Jaipur Customer');
        $response->assertSee('ONESTALL CARGO');

        // Test alias route 'hub.slip'
        $responseSlip = $this->actingAs($this->district1Manager)
            ->get(route('hub.slip', $this->shipmentDistrict1->awb_number));
        $responseSlip->assertStatus(200);
        $responseSlip->assertSee('OSCJAI001');
    }

    public function test_district_franchise_can_download_pickup_shipment_slip(): void
    {
        // Create shipment where District 1 is the pickup franchise
        $pickupShipment = Shipment::create([
            'awb_number' => 'OSCPKP004',
            'user_id' => $this->sellerUser->id,
            'pickup_franchise_id' => $this->district1Franchise->id,
            'franchise_id' => $this->district2Franchise->id,
            'receiver_name' => 'Pickup Recipient',
            'receiver_phone' => '9876543219',
            'delivery_address' => 'Kota Addr',
            'delivery_city' => 'Kota',
            'delivery_pincode' => '324005',
            'pickup_address' => 'Jaipur Pickup Point',
            'pickup_city' => 'Jaipur',
            'pickup_pincode' => '302001',
            'weight_kg' => 1.0,
            'invoice_value' => 500.00,
            'total_amount' => 70.00,
            'shipping_charge' => 70.00,
            'status' => 'Manifested',
            'shipment_type' => 'B2C',
        ]);

        $response = $this->actingAs($this->district1Manager)
            ->get(route('hub.label', $pickupShipment->awb_number));

        $response->assertStatus(200);
        $response->assertSee('OSCPKP004');
    }

    public function test_district_franchise_cannot_download_other_district_shipment_slip(): void
    {
        // District 1 tries to download District 2's shipment slip
        $response = $this->actingAs($this->district1Manager)
            ->get(route('hub.label', $this->shipmentDistrict2->awb_number));

        $response->assertStatus(403);
    }

    public function test_state_franchise_can_download_subordinate_district_shipment_slip(): void
    {
        // Rajasthan State Manager can download District 1 shipment
        $responseD1 = $this->actingAs($this->stateManager)
            ->get(route('hub.label', $this->shipmentDistrict1->awb_number));
        $responseD1->assertStatus(200);
        $responseD1->assertSee('OSCJAI001');

        // Rajasthan State Manager can download District 2 shipment
        $responseD2 = $this->actingAs($this->stateManager)
            ->get(route('hub.label', $this->shipmentDistrict2->awb_number));
        $responseD2->assertStatus(200);
        $responseD2->assertSee('OSCKOT002');
    }

    public function test_state_franchise_cannot_download_other_state_shipment_slip(): void
    {
        // Rajasthan State Manager tries to download Mumbai (Maharashtra) shipment
        $response = $this->actingAs($this->stateManager)
            ->get(route('hub.label', $this->shipmentOtherState->awb_number));

        $response->assertStatus(403);
    }

    public function test_admin_can_download_any_shipment_slip_via_admin_and_hub_routes(): void
    {
        // Admin via admin.shipments.label
        $responseAdmin1 = $this->actingAs($this->adminUser)
            ->get(route('admin.shipments.label', $this->shipmentDistrict1->awb_number));
        $responseAdmin1->assertStatus(200);
        $responseAdmin1->assertSee('OSCJAI001');

        // Admin via hub.label
        $responseAdmin2 = $this->actingAs($this->adminUser)
            ->get(route('hub.label', $this->shipmentOtherState->awb_number));
        $responseAdmin2->assertStatus(200);
        $responseAdmin2->assertSee('OSCMUM003');

        // Admin via seller.label
        $responseAdmin3 = $this->actingAs($this->adminUser)
            ->get(route('seller.label', $this->shipmentDistrict2->awb_number));
        $responseAdmin3->assertStatus(200);
        $responseAdmin3->assertSee('OSCKOT002');
    }

    public function test_missing_shipment_returns_404_not_found(): void
    {
        $response = $this->actingAs($this->district1Manager)
            ->get(route('hub.label', 'NONEXISTENT_AWB_999'));

        $response->assertStatus(404);
    }

    public function test_forged_franchise_id_in_request_is_ignored(): void
    {
        // District 1 tries to pass District 2's franchise_id as a query parameter
        $response = $this->actingAs($this->district1Manager)
            ->get(route('hub.label', [
                'awb' => $this->shipmentDistrict2->awb_number,
                'franchise_id' => $this->district2Franchise->id
            ]));

        // Must still be 403 Forbidden because authentication is derived strictly from Auth::user()
        $response->assertStatus(403);
    }

    public function test_admin_shipments_index_shows_slip_and_invoice_buttons_and_can_download_by_slip_route_and_id(): void
    {
        // 1. Admin shipments index page contains direct slip and invoice links
        $resIndex = $this->actingAs($this->adminUser)
            ->get(route('admin.shipments.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee(route('admin.shipments.label', $this->shipmentDistrict1->awb_number));
        $resIndex->assertSee('Slip');
        $resIndex->assertSee(route('admin.shipments.invoice', $this->shipmentDistrict1->awb_number));

        // 2. Admin downloads slip via admin.shipments.slip route
        $resSlip = $this->actingAs($this->adminUser)
            ->get(route('admin.shipments.slip', $this->shipmentDistrict1->awb_number));
        $resSlip->assertStatus(200);
        $resSlip->assertSee($this->shipmentDistrict1->awb_number);
        $resSlip->assertSee('Print / Save as PDF');

        // 3. Admin downloads slip by numeric database ID
        $resById = $this->actingAs($this->adminUser)
            ->get(route('admin.shipments.label', $this->shipmentDistrict1->id));
        $resById->assertStatus(200);
        $resById->assertSee($this->shipmentDistrict1->awb_number);

        // 4. Admin downloads commercial invoice
        $resInvoice = $this->actingAs($this->adminUser)
            ->get(route('admin.shipments.invoice', $this->shipmentDistrict1->awb_number));
        $resInvoice->assertStatus(200);
        $resInvoice->assertSee('Commercial Invoice');
    }

    public function test_shipping_label_does_not_display_delivery_platform_or_courier_name(): void
    {
        $courierShipment = Shipment::create([
            'awb_number' => 'OSCSECRET99',
            'user_id' => $this->sellerUser->id,
            'receiver_name' => 'Secret Customer',
            'receiver_phone' => '9876543299',
            'delivery_address' => 'Mall Road',
            'delivery_city' => 'Jaipur',
            'delivery_pincode' => '302001',
            'delivery_state' => 'Rajasthan',
            'pickup_address' => 'Seller Warehouse',
            'pickup_city' => 'Delhi',
            'pickup_pincode' => '110001',
            'weight_kg' => 1.0,
            'invoice_value' => 999.00,
            'total_amount' => 85.00,
            'shipping_charge' => 85.00,
            'status' => 'Manifested',
            'shipment_type' => 'B2C',
            'courier_partner' => 'Delhivery Surface',
        ]);

        $res = $this->actingAs($this->adminUser)
            ->get(route('admin.shipments.label', $courierShipment->awb_number));

        $res->assertStatus(200);
        $res->assertSee('OSCSECRET99');
        $res->assertSee('ONESTALL CARGO');
        // Delivery courier/platform name must NOT appear on the shipping label
        $res->assertDontSee('Delhivery Surface');
        $res->assertDontSee('DELHIVERY');
        $res->assertDontSee('<div class="courier">', false);
        // B2C / B2B shipment type indicator is removed from under barcode
        $res->assertDontSee('<div class="shipment-type">', false);
    }

    public function test_shipping_slip_shows_customer_address_and_mobile_and_hides_seller_mobile_with_support_details_at_bottom(): void
    {
        $this->sellerUser->update([
            'company_name' => 'Acme Express Retail',
            'phone' => '8384868758',
            'support_phone' => '8384868758',
            'email' => 'rishisolidgroup@gmail.com',
            'gstin' => '07AAAAA1234A1Z5',
            'company_address' => 'KK Market, Shop no 7',
            'company_city' => 'Mumbai',
            'company_state' => 'Maharashtra',
            'company_pincode' => '400001',
        ]);

        $shipment = Shipment::create([
            'awb_number' => 'AWB-88889999',
            'order_id' => '764403747',
            'user_id' => $this->sellerUser->id,
            'franchise_id' => $this->district1Franchise->id,
            'receiver_name' => 'Ujjwal kumawat',
            'receiver_phone' => '7498355538',
            'delivery_address' => 'Khatamla bus stand khatamla Rajsamand',
            'delivery_city' => 'Udaipur',
            'delivery_state' => 'Rajasthan',
            'delivery_pincode' => '313324',
            'pickup_address' => 'KK Market, Shop no 7',
            'pickup_city' => 'Mumbai',
            'pickup_pincode' => '400001',
            'product_name' => 'Watch',
            'product_qty' => 1,
            'weight_kg' => 0.5,
            'invoice_value' => 1999.00,
            'cod_amount' => 1999.00,
            'is_cod' => 1,
            'total_amount' => 150.00,
            'shipping_charge' => 150.00,
            'status' => 'Manifested',
            'shipment_type' => 'B2C',
        ]);

        // Hub downloads slip
        $hubRes = $this->actingAs($this->district1Manager)
            ->get(route('hub.label', $shipment->awb_number));
        $hubRes->assertStatus(200);

        // 1. Customer Name, complete Address and Mobile must both be clearly visible
        $hubRes->assertSee('Consignee Name:');
        $hubRes->assertSee('Ujjwal kumawat');
        $hubRes->assertSee('Shipping Address:');
        $hubRes->assertSee('Khatamla bus stand khatamla Rajsamand');
        $hubRes->assertSee('313324');
        $hubRes->assertSee('7498355538');

        // 2. Return Address is visible, but seller mobile number is hidden
        $hubRes->assertSee('Return Address:');
        $hubRes->assertSee('Acme Express Retail');
        $hubRes->assertSee('KK Market, Shop no 7');
        $hubRes->assertSee('**********');

        // 3. Support Details at bottom shows Seller Email and Contact number
        $hubRes->assertSee('Support Details');
        $hubRes->assertSee('Contact No: 8384868758');
        $hubRes->assertSee('Email ID: rishisolidgroup@gmail.com');

        // Seller downloads slip
        $sellerRes = $this->actingAs($this->sellerUser)
            ->get(route('seller.label', $shipment->awb_number));
        $sellerRes->assertStatus(200);
        $sellerRes->assertSee('Khatamla bus stand khatamla Rajsamand');
        $sellerRes->assertSee('7498355538');
        $sellerRes->assertSee('**********');
        $sellerRes->assertSee('Support Details');
        $sellerRes->assertSee('Contact No: 8384868758');

        // Admin downloads slip
        $adminRes = $this->actingAs($this->adminUser)
            ->get(route('admin.shipments.label', $shipment->awb_number));
        $adminRes->assertStatus(200);
        $adminRes->assertSee('Khatamla bus stand khatamla Rajsamand');
        $adminRes->assertSee('7498355538');
        $adminRes->assertSee('**********');
        $adminRes->assertSee('Support Details');
        $adminRes->assertSee('Contact No: 8384868758');
    }
}
