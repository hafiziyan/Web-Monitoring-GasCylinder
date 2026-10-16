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

class TransactionTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $gas;

    private Supplier $supplier;

    private Location $gudang;

    private Location $workshop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);
        $this->gudang = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
        $this->workshop = Location::create(['name' => 'Workshop', 'code' => 'LOC-003', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-TRF-'.$counter,
            'gas_type_id' => $this->gas->id,
            'supplier_id' => $this->supplier->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
            'qr_token' => 'CYL-TRFTEST'.$counter,
        ], $overrides));
    }

    private function validPayload(Cylinder $cylinder, array $overrides = []): array
    {
        return array_merge([
            'cylinder_id' => $cylinder->id,
            'to_location_id' => $this->workshop->id,
            'condition' => 'GOOD',
            'remaining_gas' => 5.00,
            'transaction_date' => '2026-10-07 09:00:00',
            'notes' => 'Pindah ke workshop.',
        ], $overrides);
    }

    public function test_guest_tidak_dapat_membuka_form_transfer(): void
    {
        $this->get('/transactions/transfer')->assertRedirect('/login');
        $this->post('/transactions/transfer', [])->assertRedirect('/login');
    }

    public function test_admin_dan_operator_dapat_membuka_form_transfer(): void
    {
        $this->actingAs($this->admin)->get('/transactions/transfer')->assertOk()->assertSee('Transfer Tabung');
        $this->actingAs($this->operator)
            ->get('/transactions/transfer')
            ->assertOk()
            ->assertSee('Scan QR')
            ->assertSee('Cari kode tabung, serial number, atau QR token...')
            ->assertSee('Pilih Tabung dari Daftar');
    }

    public function test_transfer_berhasil_untuk_available_dan_empty(): void
    {
        foreach (['AVAILABLE', 'EMPTY'] as $status) {
            $cylinder = $this->makeCylinder(['status' => $status]);

            $this->actingAs($this->operator)
                ->post('/transactions/transfer', $this->validPayload($cylinder))
                ->assertRedirect(route('cylinders.show', $cylinder));

            $cylinder->refresh();
            $this->assertSame($status, $cylinder->status->value);
            $this->assertSame($this->workshop->id, $cylinder->current_location_id);
        }
    }

    public function test_data_transaksi_transfer_tersimpan_lengkap(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)->post('/transactions/transfer', $this->validPayload($cylinder));

        $trx = Transaction::where('cylinder_id', $cylinder->id)->firstOrFail();
        $this->assertSame('TRANSFER', $trx->transaction_type->value);
        $this->assertSame($this->operator->id, $trx->user_id);
        $this->assertSame($this->gudang->id, $trx->from_location_id);
        $this->assertSame($this->workshop->id, $trx->to_location_id);
        $this->assertSame('GOOD', $trx->condition);
        $this->assertSame('5.00', $trx->remaining_gas);
        $this->assertNull($trx->destination);
        $this->assertDatabaseHas('audit_logs', ['action' => 'TRANSFER', 'module' => 'TRANSACTION']);
    }

    public function test_identitas_tabung_tidak_berubah(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'qr_token' => 'CYL-TETAP', 'capacity' => 7.50]);

        $this->actingAs($this->operator)->post('/transactions/transfer', $this->validPayload($cylinder));

        $cylinder->refresh();
        $this->assertSame('GAS-00001', $cylinder->cylinder_code);
        $this->assertSame('CYL-TETAP', $cylinder->qr_token);
        $this->assertSame($this->gas->id, $cylinder->gas_type_id);
        $this->assertSame($this->supplier->id, $cylinder->supplier_id);
        $this->assertSame('7.50', $cylinder->capacity);
    }

    #[DataProvider('statusDitolakProvider')]
    public function test_transfer_ditolak_untuk_status_tidak_valid(string $status): void
    {
        $cylinder = $this->makeCylinder([
            'status' => $status,
            'current_location_id' => $status === 'OUTSIDE' ? null : $this->gudang->id,
        ]);

        $this->actingAs($this->operator)
            ->post('/transactions/transfer', $this->validPayload($cylinder))
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

    public function test_transfer_ditolak_jika_tujuan_sama_dengan_lokasi_saat_ini(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/transfer', $this->validPayload($cylinder, ['to_location_id' => $this->gudang->id]))
            ->assertRedirect();

        $this->assertSame(0, Transaction::count());
    }

    public function test_validasi_transfer(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/transfer', $this->validPayload($cylinder, ['to_location_id' => 9999]))
            ->assertSessionHasErrors('to_location_id');

        $this->actingAs($this->operator)
            ->post('/transactions/transfer', $this->validPayload($cylinder, ['remaining_gas' => -1]))
            ->assertSessionHasErrors('remaining_gas');

        $this->assertSame(0, Transaction::count());
    }

    public function test_kegagalan_tidak_menyimpan_sebagian(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/transfer', $this->validPayload($cylinder, ['cylinder_id' => 9999]))
            ->assertSessionHasErrors('cylinder_id');

        $this->assertSame(0, Transaction::count());
        $this->assertSame($this->gudang->id, $cylinder->refresh()->current_location_id);
    }

    public function test_histori_transfer_muncul_pada_detail(): void
    {
        $cylinder = $this->makeCylinder();
        $this->actingAs($this->operator)
            ->followingRedirects()
            ->post('/transactions/transfer', $this->validPayload($cylinder))
            ->assertSee('Transaksi Transfer berhasil disimpan.')
            ->assertSee('Riwayat Transaksi')
            ->assertSee('TRANSFER');
    }

    public function test_transfer_ditolak_jika_lokasi_sama_dan_pilihan_bertahan(): void
    {
        $cylinder = $this->makeCylinder();

        // Penolakan di dalam controller memakai back(): form render ulang
        // harus tetap menampilkan tabung yang dipilih.
        $response = $this->actingAs($this->operator)
            ->from('/transactions/transfer')
            ->post('/transactions/transfer', $this->validPayload($cylinder, [
                'to_location_id' => $this->gudang->id,
            ]))
            ->assertRedirect('/transactions/transfer')
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::count());

        $this->actingAs($this->operator)
            ->get('/transactions/transfer')
            ->assertOk()
            ->assertSee('"cylinder_code":"'.$cylinder->cylinder_code.'"', false);
    }
}
