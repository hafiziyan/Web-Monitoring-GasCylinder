<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionMaintenanceCompleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $gas;

    private Supplier $supplier;

    private Location $maintenanceArea;

    private Location $workshop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);
        $this->maintenanceArea = Location::create(['name' => 'Maintenance Area', 'code' => 'LOC-004', 'is_active' => true]);
        $this->workshop = Location::create(['name' => 'Workshop Gas', 'code' => 'LOC-006', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-MC-'.$counter,
            'gas_type_id' => $this->gas->id,
            'supplier_id' => $this->supplier->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'MAINTENANCE',
            'condition' => 'MAINTENANCE',
            'current_location_id' => $this->maintenanceArea->id,
            'qr_token' => 'CYL-MCTEST'.$counter,
        ], $overrides));
    }

    private function validPayload(Cylinder $cylinder, array $overrides = []): array
    {
        return array_merge([
            'cylinder_id' => $cylinder->id,
            'to_location_id' => $this->workshop->id,
            'final_status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'remaining_gas' => 6.00,
            'transaction_date' => '2026-10-05 14:00:00',
            'notes' => 'Maintenance selesai, tabung siap digunakan.',
        ], $overrides);
    }

    public function test_guest_tidak_dapat_membuka_form(): void
    {
        $this->get('/transactions/maintenance/complete')->assertRedirect('/login');
        $this->post('/transactions/maintenance/complete', [])->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_form(): void
    {
        $this->actingAs($this->admin)->get('/transactions/maintenance/complete')->assertOk()->assertSee('Maintenance Selesai');
    }

    public function test_operator_dapat_membuka_form(): void
    {
        $this->actingAs($this->operator)->get('/transactions/maintenance/complete')->assertOk()->assertSee('Maintenance Selesai');
    }

    public function test_complete_berhasil_dan_status_lokasi_berubah(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder))
            ->assertRedirect(route('cylinders.show', $cylinder));

        $cylinder->refresh();
        $this->assertSame('AVAILABLE', $cylinder->status->value);
        $this->assertSame($this->workshop->id, $cylinder->current_location_id);
        $this->assertSame('GOOD', $cylinder->condition->value);
    }

    public function test_status_akhir_empty_didukung(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder, ['final_status' => 'EMPTY']))
            ->assertRedirect();

        $this->assertSame('EMPTY', $cylinder->refresh()->status->value);
    }

    public function test_data_transaksi_tersimpan_lengkap(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)->post('/transactions/maintenance/complete', $this->validPayload($cylinder));

        $trx = Transaction::where('cylinder_id', $cylinder->id)->firstOrFail();
        $this->assertSame('MAINTENANCE', $trx->transaction_type->value);
        $this->assertSame($this->operator->id, $trx->user_id);
        $this->assertSame($this->maintenanceArea->id, $trx->from_location_id);
        $this->assertSame($this->workshop->id, $trx->to_location_id);
        $this->assertSame('GOOD', $trx->condition);
        $this->assertSame('6.00', $trx->remaining_gas);
        $this->assertStringContainsString('Maintenance selesai - status: AVAILABLE', $trx->notes);
        $this->assertNull($trx->destination);
    }

    public function test_identitas_tabung_tidak_berubah(): void
    {
        $cylinder = $this->makeCylinder([
            'cylinder_code' => 'GAS-00001',
            'serial_number' => 'SN-00001',
            'qr_token' => 'CYL-TETAP',
            'capacity' => 7.50,
        ]);

        $this->actingAs($this->operator)->post('/transactions/maintenance/complete', $this->validPayload($cylinder));

        $cylinder->refresh();
        $this->assertSame('GAS-00001', $cylinder->cylinder_code);
        $this->assertSame('SN-00001', $cylinder->serial_number);
        $this->assertSame('CYL-TETAP', $cylinder->qr_token);
        $this->assertSame($this->gas->id, $cylinder->gas_type_id);
        $this->assertSame($this->supplier->id, $cylinder->supplier_id);
        $this->assertSame('7.50', $cylinder->capacity);
    }

    #[DataProvider('statusDitolakProvider')]
    public function test_ditolak_untuk_status_bukan_maintenance(string $status): void
    {
        $cylinder = $this->makeCylinder([
            'status' => $status,
            'current_location_id' => $status === 'OUTSIDE' ? null : $this->maintenanceArea->id,
        ]);

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder))
            ->assertRedirect();

        $this->assertSame(0, Transaction::count());
        $this->assertSame($status, $cylinder->refresh()->status->value);
    }

    public static function statusDitolakProvider(): array
    {
        return [
            'AVAILABLE' => ['AVAILABLE'],
            'EMPTY' => ['EMPTY'],
            'OUTSIDE' => ['OUTSIDE'],
            'IN_USE' => ['IN_USE'],
            'INACTIVE' => ['INACTIVE'],
        ];
    }

    public function test_lokasi_wajib_valid_dan_aktif(): void
    {
        $cylinder = $this->makeCylinder();
        $inactive = Location::create(['name' => 'Lama', 'code' => 'LOC-099', 'is_active' => false]);

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder, ['to_location_id' => 9999]))
            ->assertSessionHasErrors('to_location_id');

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder, ['to_location_id' => $inactive->id]))
            ->assertSessionHasErrors('to_location_id');

        $this->assertSame(0, Transaction::count());
    }

    public function test_status_akhir_hanya_available_atau_empty(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder, ['final_status' => 'OUTSIDE']))
            ->assertSessionHasErrors('final_status');

        $this->assertSame(0, Transaction::count());
    }

    public function test_remaining_gas_tidak_boleh_negatif(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder, ['remaining_gas' => -1]))
            ->assertSessionHasErrors('remaining_gas');

        $this->assertSame(0, Transaction::count());
    }

    public function test_kegagalan_tidak_menyimpan_sebagian(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder, ['cylinder_id' => 9999]))
            ->assertSessionHasErrors('cylinder_id');

        $this->assertSame(0, Transaction::count());
        $this->assertSame('MAINTENANCE', $cylinder->refresh()->status->value);
    }

    public function test_tombol_hanya_untuk_status_maintenance(): void
    {
        $maintenance = $this->makeCylinder(['qr_token' => 'CYL-MC1']);
        $available = $this->makeCylinder([
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'qr_token' => 'CYL-MC2',
        ]);

        $this->actingAs($this->operator)
            ->get('/transactions/scan/CYL-MC1')
            ->assertSee('Maintenance Selesai')
            ->assertSee(route('transactions.maintenance.complete', ['cylinder' => $maintenance->cylinder_code]), false);

        $response = $this->actingAs($this->operator)->get('/transactions/scan/CYL-MC2');
        $response->assertOk();
        $response->assertDontSee('Maintenance Selesai');

        $this->actingAs($this->operator)
            ->get(route('cylinders.show', $maintenance))
            ->assertSee('Maintenance Selesai');
    }

    public function test_histori_complete_muncul_pada_detail(): void
    {
        $cylinder = $this->makeCylinder();
        $this->actingAs($this->operator)
            ->followingRedirects()
            ->post('/transactions/maintenance/complete', $this->validPayload($cylinder))
            ->assertSee('Maintenance tabung berhasil diselesaikan.')
            ->assertSee('Riwayat Transaksi')
            ->assertSee('MAINTENANCE')
            ->assertSee('Maintenance selesai - status: AVAILABLE');
    }
}
