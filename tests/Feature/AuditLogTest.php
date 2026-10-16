<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $gas;

    private Location $gudang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->gudang = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-AUD-'.$counter,
            'gas_type_id' => $this->gas->id,
            'unit' => 'm3',
            'status' => 'EMPTY',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
            'qr_token' => 'CYL-AUD'.$counter,
        ], $overrides));
    }

    private function inPayload(Cylinder $cylinder): array
    {
        return [
            'cylinder_id' => $cylinder->id,
            'to_location_id' => $this->gudang->id,
            'condition' => 'GOOD',
            'transaction_date' => '2026-10-01 09:00:00',
        ];
    }

    private function outPayload(Cylinder $cylinder): array
    {
        return [
            'cylinder_id' => $cylinder->id,
            'destination' => 'PT ABC',
            'condition' => 'GOOD',
            'transaction_date' => '2026-10-02 10:00:00',
        ];
    }

    public function test_guest_tidak_dapat_membuka_audit_log(): void
    {
        $this->get('/audit-logs')->assertRedirect('/login');
    }

    public function test_operator_tidak_dapat_membuka_audit_log(): void
    {
        $this->actingAs($this->operator)->get('/audit-logs')->assertForbidden();
        $log = AuditLog::create(['action' => 'X', 'module' => 'Y', 'description' => 'Z']);
        $this->actingAs($this->operator)->get(route('audit-logs.show', $log))->assertForbidden();
    }

    public function test_admin_dapat_membuka_audit_log(): void
    {
        AuditLogService::log($this->admin, 'LOGIN', 'AUTH', 'User berhasil login.');

        $this->actingAs($this->admin)
            ->get('/audit-logs')
            ->assertOk()
            ->assertSee('User berhasil login.');
    }

    public function test_filter_dan_pagination(): void
    {
        AuditLogService::log($this->admin, 'LOGIN', 'AUTH', 'Admin masuk.');
        AuditLogService::log($this->operator, 'IN', 'TRANSACTION', 'Tabung GAS-00001 masuk.');

        $this->actingAs($this->admin)->get('/audit-logs?search=masuk')->assertOk()->assertSee('Tabung GAS-00001');
        $this->actingAs($this->admin)->get('/audit-logs?user_id='.$this->operator->id)->assertOk()->assertSee('Tabung GAS-00001');
        $this->actingAs($this->admin)->get('/audit-logs?action=LOGIN')->assertOk()->assertSee('Admin masuk.');
        $this->actingAs($this->admin)->get('/audit-logs?module=TRANSACTION')->assertOk()->assertSee('Tabung GAS-00001');
        $today = now()->format('Y-m-d');
        $this->actingAs($this->admin)->get("/audit-logs?date_from={$today}&date_to={$today}")->assertOk()->assertSee('Admin masuk.');
    }

    public function test_date_to_mencakup_235959(): void
    {
        $log = AuditLogService::log($this->admin, 'LOGIN', 'AUTH', 'Malam hari.');
        // created_at bukan fillable: update via query builder.
        AuditLog::whereKey($log->id)->update(['created_at' => '2026-10-06 23:59:59']);

        $this->actingAs($this->admin)
            ->get('/audit-logs?date_to=2026-10-06')
            ->assertOk()
            ->assertSee('Malam hari.');
    }

    public function test_detail_dapat_dibuka_admin(): void
    {
        $log = AuditLogService::log($this->admin, 'IN', 'TRANSACTION', 'Detail test.', null, ['a' => 1], ['a' => 2]);

        $this->actingAs($this->admin)
            ->get(route('audit-logs.show', $log))
            ->assertOk()
            ->assertSee('Detail test.')
            ->assertSee('Old Values')
            ->assertSee('New Values');
    }

    public function test_audit_log_tidak_dapat_diedit_atau_dihapus(): void
    {
        $log = AuditLogService::log($this->admin, 'LOGIN', 'AUTH', 'X.');

        foreach (['put', 'patch', 'delete'] as $method) {
            $this->actingAs($this->admin)->{$method}('/audit-logs/'.$log->id)->assertStatus(405);
            $this->actingAs($this->operator)->{$method}('/audit-logs/'.$log->id)->assertStatus(405);
        }
        $this->actingAs($this->admin)->post('/audit-logs')->assertStatus(405);

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    public function test_login_berhasil_membuat_audit(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id, 'action' => 'LOGIN', 'module' => 'AUTH',
        ]);
    }

    public function test_logout_membuat_audit(): void
    {
        $this->actingAs($this->operator)->post('/logout')->assertRedirect('/');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->operator->id, 'action' => 'LOGOUT', 'module' => 'AUTH',
        ]);
        $this->assertGuest();
    }

    public function test_transaksi_in_membuat_audit(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)->post('/transactions/in', $this->inPayload($cylinder))->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->operator->id, 'action' => 'IN', 'module' => 'TRANSACTION',
        ]);
    }

    public function test_transaksi_out_membuat_audit(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'AVAILABLE']);

        $this->actingAs($this->operator)->post('/transactions/out', $this->outPayload($cylinder))->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'OUT', 'module' => 'TRANSACTION',
        ]);
    }

    public function test_return_membuat_audit(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'OUTSIDE', 'current_location_id' => null]);

        $this->actingAs($this->operator)->post('/transactions/return', [
            'cylinder_id' => $cylinder->id,
            'to_location_id' => $this->gudang->id,
            'condition' => 'GOOD',
            'transaction_date' => '2026-10-03 11:00:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'RETURN', 'module' => 'TRANSACTION']);
    }

    public function test_transfer_didukung_service_audit(): void
    {
        // Belum ada flow TRANSFER di aplikasi; pastikan action-nya didukung service.
        $log = AuditLogService::log(
            $this->operator, 'TRANSFER', 'TRANSACTION', 'Tabung GAS-00001 dipindahkan dari Gudang Gas ke Workshop.'
        );

        $this->assertSame('TRANSFER', $log->action);
        $this->assertSame('TRANSACTION', $log->module);
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id, 'action' => 'TRANSFER']);
    }

    public function test_maintenance_dan_complete_membuat_audit(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'AVAILABLE']);

        $this->actingAs($this->operator)->post('/transactions/maintenance', [
            'cylinder_id' => $cylinder->id,
            'maintenance_type' => 'Pemeriksaan rutin',
            'to_location_id' => $this->gudang->id,
            'condition' => 'MAINTENANCE',
            'transaction_date' => '2026-10-04 10:00:00',
        ])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'MAINTENANCE', 'module' => 'TRANSACTION']);

        $this->actingAs($this->operator)->post('/transactions/maintenance/complete', [
            'cylinder_id' => $cylinder->id,
            'to_location_id' => $this->gudang->id,
            'final_status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'transaction_date' => '2026-10-05 10:00:00',
        ])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'MAINTENANCE_COMPLETE', 'module' => 'TRANSACTION']);
    }

    public function test_cylinder_create_update_membuat_audit(): void
    {
        $this->actingAs($this->admin)->post('/cylinders', [
            'cylinder_code' => 'GAS-00001',
            'gas_type_id' => $this->gas->id,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREATE_CYLINDER', 'module' => 'MASTER']);

        $cylinder = Cylinder::where('cylinder_code', 'GAS-00001')->firstOrFail();
        $this->actingAs($this->admin)->put(route('cylinders.update', $cylinder), [
            'gas_type_id' => $this->gas->id,
            'capacity' => 7.50,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
        ])->assertRedirect();

        $log = AuditLog::where('action', 'UPDATE_CYLINDER')->firstOrFail();
        $this->assertSame('MASTER', $log->module);
        $this->assertNotNull($log->old_values);
        $this->assertNotNull($log->new_values);
    }

    public function test_export_membuat_audit(): void
    {
        $this->actingAs($this->admin)->get('/reports/transactions/excel')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'EXPORT_EXCEL', 'module' => 'REPORT']);

        $this->actingAs($this->admin)->get('/reports/cylinders/pdf')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'EXPORT_PDF', 'module' => 'REPORT']);
    }

    public function test_audit_rollback_jika_transaction_gagal(): void
    {
        try {
            DB::transaction(function () {
                AuditLogService::log($this->admin, 'IN', 'TRANSACTION', 'Akan di-rollback.');
                throw new \RuntimeException('gagal');
            });
        } catch (\RuntimeException) {
        }

        $this->assertDatabaseMissing('audit_logs', ['description' => 'Akan di-rollback.']);
    }

    public function test_qr_token_tidak_berubah_karena_audit(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'AVAILABLE', 'qr_token' => 'CYL-TETAP']);
        $before = $cylinder->qr_token;

        $this->actingAs($this->operator)->post('/transactions/out', $this->outPayload($cylinder))->assertRedirect();

        $this->assertSame($before, $cylinder->refresh()->qr_token);
        $this->assertDatabaseHas('audit_logs', ['action' => 'OUT']);
    }

    public function test_generate_qr_membuat_audit(): void
    {
        $cylinder = $this->makeCylinder(['qr_token' => null]);

        $this->actingAs($this->admin)
            ->post(route('qr-codes.generate', $cylinder))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'GENERATE_QR', 'module' => 'QR']);
    }

    public function test_operator_tidak_dapat_mengakses_admin_route(): void
    {
        $this->actingAs($this->operator)->get('/audit-logs')->assertForbidden();
        $this->actingAs($this->operator)->get('/admin/users')->assertForbidden();
    }
}
