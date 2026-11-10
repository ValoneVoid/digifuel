<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FuelRequestHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditRetentionAndPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_purges_old_audit_logs_correctly(): void
    {
        // 1. Buat log audit lama (> 180 hari)
        $oldLog = FuelRequestHistory::record(
            null,
            null,
            'test_old_action',
            null,
            null,
            'Old log note'
        );
        $oldLog->created_at = Carbon::now()->subDays(200);
        $oldLog->save();

        // 2. Buat log audit baru (< 180 hari)
        $newLog = FuelRequestHistory::record(
            null,
            null,
            'test_recent_action',
            null,
            null,
            'Recent log note'
        );

        $this->assertEquals(2, FuelRequestHistory::count());

        // 3. Jalankan command purge via Artisan facade
        $exitCode = Artisan::call('audit:purge', [
            '--days' => 180,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Old log terhapus, new log tetap ada, dan 1 log audit baru (audit_purged) dicatat
        $this->assertDatabaseMissing('fuel_request_history', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('fuel_request_history', ['id' => $newLog->id]);
        $this->assertDatabaseHas('fuel_request_history', ['action' => 'audit_purged']);
    }

    public function test_admin_can_export_audit_trail_to_excel(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        FuelRequestHistory::record(
            null,
            $admin->id,
            'export_test_action',
            null,
            null,
            'Catatan tes export Excel'
        );

        $response = $this->actingAs($admin)->get(route('admin.master.audit.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $response->headers->get('content-type'));
    }

    public function test_admin_can_purge_audit_logs_via_web_with_two_step_verification(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Buat log lama (> 180 hari)
        $oldLog = FuelRequestHistory::record(
            null,
            $admin->id,
            'web_purge_action',
            null,
            null,
            'Log lama untuk pembersihan web'
        );
        $oldLog->created_at = Carbon::now()->subDays(190);
        $oldLog->save();

        // Coba purge dengan keyword salah
        $failResponse = $this->actingAs($admin)->post(route('admin.master.audit.purge'), [
            'retention_days' => 180,
            'confirm_keyword' => 'SALAH',
        ]);

        $failResponse->assertRedirect(route('admin.master', ['tab' => 'audit']));
        $failResponse->assertSessionHas('error');
        $this->assertDatabaseHas('fuel_request_history', ['id' => $oldLog->id]);

        // Purge dengan keyword benar "BERSIHKAN"
        $successResponse = $this->actingAs($admin)->post(route('admin.master.audit.purge'), [
            'retention_days' => 180,
            'confirm_keyword' => 'BERSIHKAN',
        ]);

        $successResponse->assertRedirect(route('admin.master', ['tab' => 'audit']));
        $successResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('fuel_request_history', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('fuel_request_history', ['action' => 'audit_purged']);
    }
}
