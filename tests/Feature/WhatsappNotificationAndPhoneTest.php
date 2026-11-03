<?php

namespace Tests\Feature;

use App\Models\FuelRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FonnteWhatsappService;
use Database\Seeders\DigiFuelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappNotificationAndPhoneTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $driver;
    protected User $spv;
    protected User $depthead;
    protected User $vendorUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DigiFuelSeeder::class);

        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->driver = User::where('username', 'adji')->firstOrFail();
        $this->spv = User::where('username', 'aklis')->firstOrFail();
        $this->depthead = User::where('username', 'Pdh-kusan')->firstOrFail();
        $this->vendorUser = User::where('username', 'vendor_sudan')->firstOrFail();
    }

    public function test_user_can_update_phone_on_profile_page_with_08_format(): void
    {
        $response = $this->actingAs($this->driver)
            ->post(route('profile.update'), [
                'name' => $this->driver->name,
                'username' => $this->driver->username,
                'email' => $this->driver->email,
                'phone' => '0812-9876-5432', // With hyphens to test auto-clean
                'nrp' => $this->driver->nrp,
                'jabatan' => $this->driver->jabatan,
                'company' => $this->driver->company,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profile.index'));

        $this->driver->refresh();
        $this->assertEquals('081298765432', $this->driver->phone);

        // Test invalid format (must start with 08)
        $invalidResp = $this->actingAs($this->driver)
            ->post(route('profile.update'), [
                'name' => $this->driver->name,
                'username' => $this->driver->username,
                'email' => $this->driver->email,
                'phone' => '071234567890',
                'jabatan' => $this->driver->jabatan,
                'company' => $this->driver->company,
            ]);

        $invalidResp->assertSessionHasErrors(['phone']);
    }

    public function test_admin_can_store_and_update_user_with_phone_number(): void
    {
        // Store user
        $storeResp = $this->actingAs($this->admin)
            ->postJson(route('admin.master.user'), [
                'name' => 'Operator Baru',
                'username' => 'op_baru',
                'email' => 'op.baru@satui.unitedtractors.com',
                'phone' => '0852-1122-3344',
                'role' => 'driver',
                'jabatan' => 'Operator Alat Berat',
                'nrp' => '998811',
                'password' => 'password123',
                'is_active' => true,
            ]);

        $storeResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $created = User::where('username', 'op_baru')->firstOrFail();
        $this->assertEquals('085211223344', $created->phone);

        // Update user
        $updateResp = $this->actingAs($this->admin)
            ->postJson(route('admin.master.user'), [
                'id' => $created->id,
                'name' => 'Operator Baru Edit',
                'username' => 'op_baru',
                'email' => 'op.baru@satui.unitedtractors.com',
                'phone' => '087812345678',
                'role' => 'driver',
                'jabatan' => 'Senior Operator',
                'nrp' => '998811',
                'is_active' => true,
            ]);

        $updateResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $created->refresh();
        $this->assertEquals('087812345678', $created->phone);
    }

    public function test_fonnte_whatsapp_service_dispatches_notifications_only_for_approval_stages(): void
    {
        $mockWhatsapp = $this->createMock(FonnteWhatsappService::class);

        // 1. When request is submitted -> notify SPV
        $mockWhatsapp->expects($this->once())
            ->method('notifyPendingSpv')
            ->willReturn(true);

        // 2. When SPV approves -> notify Dept Head
        $mockWhatsapp->expects($this->once())
            ->method('notifyPendingDepthead')
            ->willReturn(true);

        // 3. When Dept Head approves -> notify Vendor
        $mockWhatsapp->expects($this->once())
            ->method('notifyReadyForVendor')
            ->willReturn(true);

        $this->app->instance(FonnteWhatsappService::class, $mockWhatsapp);

        $fuelman = User::where('username', 'adya')->firstOrFail();

        // Step 1: Driver submits fuel request
        $createResp = $this->actingAs($this->driver)
            ->post('/fuel', [
                'request_date' => '2026-07-01 10:00:00',
                'unit_code' => 'UT 208 DA 8191',
                'police_no' => 'DA 8191',
                'total_liters' => 35,
                'hm_km' => 110000,
                'area' => 'SDU (Sungai Danau)',
                'spv_id' => $this->spv->id,
                'fuelman_id' => $fuelman->id,
                'depthead_id' => $this->depthead->id,
                'vendor_name' => 'Cv. Sudan Mandiri',
                'action_type' => 'submit',
            ]);

        $createResp->assertRedirect(route('history.index'));
        $fuelRequest = FuelRequest::latest('id')->firstOrFail();
        $this->assertEquals('pending_spv', $fuelRequest->current_stage);

        // Step 2: SPV approves request -> forwarded to Dept Head
        $spvResp = $this->actingAs($this->spv)
            ->post(route('approval.approve', $fuelRequest->id), [
                'note' => 'Disetujui SPV untuk operasional.',
            ]);

        $spvResp->assertRedirect();

        $fuelRequest->refresh();
        $this->assertEquals('pending_manager', $fuelRequest->current_stage);

        // Step 3: Dept Head approves request -> forwarded to Vendor
        $deptheadResp = $this->actingAs($this->depthead)
            ->post(route('approval.approve', $fuelRequest->id), [
                'note' => 'Disetujui Dept Head, teruskan ke Vendor.',
            ]);

        $deptheadResp->assertRedirect();

        $fuelRequest->refresh();
        $this->assertEquals('pending_vendor', $fuelRequest->current_stage);
    }

    public function test_fonnte_service_handles_network_failure_safely(): void
    {
        Http::fake([
            'https://api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'server maintenance'], 500),
        ]);

        config(['services.fonnte.enable_in_tests' => true]);

        $service = new FonnteWhatsappService('fake-token', 'https://api.fonnte.com/send');

        // sendMessage must return false and NOT throw an exception
        $result = $service->sendMessage('081234567890', 'Test message');
        $this->assertFalse($result);

        // sendMessage with empty number should return false gracefully
        $emptyResult = $service->sendMessage('', 'Test message');
        $this->assertFalse($emptyResult);
    }
}
