<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $gas;

    private Location $gudang;

    private Location $maintenanceArea;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->gudang = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
        $this->maintenanceArea = Location::create(['name' => 'Maintenance Area', 'code' => 'LOC-004', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-MNT-'.$counter,
            'gas_type_id' => $this->gas->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
            'qr_token' => 'CYL-MNTTEST'.$counter,
        ], $overrides));
    }

    private function validPayload(Cylinder $cylinder, array $overrides = []): array
    {
        return array_merge([
            'cylinder_id' => $cylinder->id,
            'maintenance_type' => 'Perbaikan valve',
            'to_location_id' => $this->maintenanceArea->id,
            'condition' => 'MAINTENANCE',
            'remaining_gas' => 1.50,
            'transaction_date' => '2026-10-04 10:00:00',
            'notes' => 'Valve perlu diperiksa.',
        ], $overrides);
    }

    public function test_guest_tidak_dapat_membuka_form_maintenance(): void
    {
        $this->get('/transactions/maintenance')->assertRedirect('/login');
        $this->post('/transactions/maintenance', [])->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_form_maintenance(): void
    {
        $this->actingAs($this->admin)->get('/transactions/maintenance')->assertOk()->assertSee('Maintenance Tabung');
    }

    public function test_operator_dapat_membuka_form_maintenance(): void
    {
        $this->actingAs($this->operator)->get('/transactions/maintenance')->assertOk()->assertSee('Jenis Maintenance');
    }

    public function test_maintenance_berhasil_untuk_available_dan_empty(): void
    {
        foreach (['AVAILABLE', 'EMPTY'] as $status) {
            $cylinder = $this->makeCylinder(['status' => $status]);

            $this->actingAs($this->operator)
                ->post('/transactions/maintenance', $this->validPayload($cylinder))
                ->assertRedirect(route('cylinders.show', $cylinder));

            $cylinder->refresh();
            $this->assertSame('MAINTENANCE', $cylinder->status->value);
            $this->assertSame($this->maintenanceArea->id, $cylinder->current_location_id);
            $this->assertSame('MAINTENANCE', $cylinder->condition->value);
        }
    }

    public function test_data_transaksi_tersimpan_lengkap(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)->post('/transactions/maintenance', $this->validPayload($cylinder));

        $trx = Transaction::where('cylinder_id', $cylinder->id)->firstOrFail();
        $this->assertSame('MAINTENANCE', $trx->transaction_type->value);
        $this->assertSame($this->operator->id, $trx->user_id);
        $this->assertSame($this->gudang->id, $trx->from_location_id);
        $this->assertSame($this->maintenanceArea->id, $trx->to_location_id);
        $this->assertSame('MAINTENANCE', $trx->condition);
        $this->assertSame('1.50', $trx->remaining_gas);
        $this->assertStringContainsString('Perbaikan valve', $trx->notes);
        $this->assertStringContainsString('Valve perlu diperiksa.', $trx->notes);
        $this->assertNull($trx->destination);
    }

    public function test_qr_token_dan_cylinder_code_tidak_berubah(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'qr_token' => 'CYL-TETAP']);

        $this->actingAs($this->operator)->post('/transactions/maintenance', $this->validPayload($cylinder));

        $cylinder->refresh();
        $this->assertSame('GAS-00001', $cylinder->cylinder_code);
        $this->assertSame('CYL-TETAP', $cylinder->qr_token);
    }

    #[DataProvider('statusDitolakProvider')]
    public function test_maintenance_ditolak_untuk_status_tidak_valid(string $status): void
    {
        $cylinder = $this->makeCylinder([
            'status' => $status,
            'current_location_id' => $status === 'OUTSIDE' ? null : $this->gudang->id,
        ]);

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance', $this->validPayload($cylinder))
            ->assertRedirect();

        $this->assertSame(0, Transaction::count());
        $this->assertSame($status, $cylinder->refresh()->status->value);
    }

    public static function statusDitolakProvider(): array
    {
        return [
            'OUTSIDE' => ['OUTSIDE'],
            'INACTIVE' => ['INACTIVE'],
            'MAINTENANCE' => ['MAINTENANCE'],
            'IN_USE' => ['IN_USE'],
        ];
    }

    public function test_lokasi_maintenance_wajib_valid(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance', $this->validPayload($cylinder, ['to_location_id' => 9999]))
            ->assertSessionHasErrors('to_location_id');

        $this->assertSame(0, Transaction::count());
    }

    public function test_remaining_gas_tidak_boleh_negatif(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance', $this->validPayload($cylinder, ['remaining_gas' => -1]))
            ->assertSessionHasErrors('remaining_gas');

        $this->assertSame(0, Transaction::count());
    }

    public function test_maintenance_type_wajib_dari_daftar_pengaturan(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance', $this->validPayload($cylinder, ['maintenance_type' => 'Servis abal-abal']))
            ->assertSessionHasErrors('maintenance_type');

        $this->assertSame(0, Transaction::count());
        $this->assertSame('AVAILABLE', $cylinder->refresh()->status->value);
    }

    public function test_kegagalan_tidak_menyimpan_sebagian(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance', $this->validPayload($cylinder, ['cylinder_id' => 9999]))
            ->assertSessionHasErrors('cylinder_id');

        $this->assertSame(0, Transaction::count());
        $this->assertSame('AVAILABLE', $cylinder->refresh()->status->value);
    }

    public function test_histori_maintenance_muncul_pada_detail(): void
    {
        $cylinder = $this->makeCylinder();
        $this->actingAs($this->operator)
            ->followingRedirects()
            ->post('/transactions/maintenance', $this->validPayload($cylinder))
            ->assertSee('Tabung berhasil masuk maintenance.')
            ->assertSee('Riwayat Transaksi')
            ->assertSee('MAINTENANCE')
            ->assertSee('Perbaikan valve');
    }

    public function test_dashboard_menghitung_status_maintenance(): void
    {
        $this->makeCylinder(['status' => 'MAINTENANCE', 'current_location_id' => $this->maintenanceArea->id]);

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-test="stat-maintenance">1<', false);
    }
}
