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

class TransactionReturnTest extends TestCase
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
            'serial_number' => 'SN-RET-'.$counter,
            'gas_type_id' => $this->gas->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'OUTSIDE',
            'condition' => 'GOOD',
            'current_location_id' => null,
            'qr_token' => 'CYL-RETTEST'.$counter,
        ], $overrides));
    }

    private function validPayload(Cylinder $cylinder, array $overrides = []): array
    {
        return array_merge([
            'cylinder_id' => $cylinder->id,
            'to_location_id' => $this->gudang->id,
            'condition' => 'GOOD',
            'remaining_gas' => 2.50,
            'transaction_date' => '2026-10-03 11:00:00',
            'notes' => 'Kembali dari pelanggan.',
        ], $overrides);
    }

    public function test_guest_tidak_dapat_membuka_form_return(): void
    {
        $this->get('/transactions/return')->assertRedirect('/login');
        $this->post('/transactions/return', [])->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_form_return(): void
    {
        $this->actingAs($this->admin)->get('/transactions/return')->assertOk()->assertSee('Tabung Kembali');
    }

    public function test_operator_dapat_membuka_form_return(): void
    {
        $this->actingAs($this->operator)->get('/transactions/return')->assertOk()->assertSee('Tabung Kembali');
    }

    public function test_return_berhasil_untuk_outside(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/return', $this->validPayload($cylinder))
            ->assertRedirect(route('cylinders.show', $cylinder));

        $cylinder->refresh();
        $this->assertSame('AVAILABLE', $cylinder->status->value);
        $this->assertSame($this->gudang->id, $cylinder->current_location_id);
        $this->assertSame('GOOD', $cylinder->condition->value);

        $trx = Transaction::where('cylinder_id', $cylinder->id)->firstOrFail();
        $this->assertSame('RETURN', $trx->transaction_type->value);
        $this->assertSame($this->operator->id, $trx->user_id);
        $this->assertSame($this->gudang->id, $trx->to_location_id);
        $this->assertSame('GOOD', $trx->condition);
        $this->assertSame('2.50', $trx->remaining_gas);
        $this->assertNull($trx->from_location_id);
        $this->assertNull($trx->destination);
    }

    public function test_qr_token_dan_cylinder_code_tidak_berubah(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'qr_token' => 'CYL-TETAP']);

        $this->actingAs($this->operator)->post('/transactions/return', $this->validPayload($cylinder));

        $cylinder->refresh();
        $this->assertSame('GAS-00001', $cylinder->cylinder_code);
        $this->assertSame('CYL-TETAP', $cylinder->qr_token);
    }

    #[DataProvider('statusDitolakProvider')]
    public function test_return_ditolak_untuk_status_bukan_outside(string $status): void
    {
        $cylinder = $this->makeCylinder(['status' => $status, 'current_location_id' => $this->gudang->id]);

        $this->actingAs($this->operator)
            ->post('/transactions/return', $this->validPayload($cylinder))
            ->assertRedirect();

        $this->assertSame(0, Transaction::count());
        $this->assertSame($status, $cylinder->refresh()->status->value);
    }

    public static function statusDitolakProvider(): array
    {
        return [
            'AVAILABLE' => ['AVAILABLE'],
            'EMPTY' => ['EMPTY'],
            'IN_USE' => ['IN_USE'],
            'MAINTENANCE' => ['MAINTENANCE'],
            'INACTIVE' => ['INACTIVE'],
        ];
    }

    public function test_lokasi_return_wajib_valid(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/return', $this->validPayload($cylinder, ['to_location_id' => 9999]))
            ->assertSessionHasErrors('to_location_id');

        $this->assertSame(0, Transaction::count());
    }

    public function test_remaining_gas_tidak_boleh_negatif(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/return', $this->validPayload($cylinder, ['remaining_gas' => -1]))
            ->assertSessionHasErrors('remaining_gas');

        $this->assertSame(0, Transaction::count());
    }

    public function test_kegagalan_tidak_menyimpan_sebagian(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/return', $this->validPayload($cylinder, ['cylinder_id' => 9999]))
            ->assertSessionHasErrors('cylinder_id');

        $this->assertSame(0, Transaction::count());
        $this->assertSame('OUTSIDE', $cylinder->refresh()->status->value);
    }

    public function test_tombol_return_hanya_untuk_outside(): void
    {
        $outside = $this->makeCylinder(['qr_token' => 'CYL-R1']);
        $available = $this->makeCylinder([
            'status' => 'AVAILABLE',
            'current_location_id' => $this->gudang->id,
            'qr_token' => 'CYL-R2',
        ]);

        $this->actingAs($this->operator)
            ->get('/transactions/scan/CYL-R1')
            ->assertSee('Tabung Kembali')
            ->assertSee(route('transactions.return.create', ['cylinder' => $outside->cylinder_code]), false);

        $response = $this->actingAs($this->operator)->get('/transactions/scan/CYL-R2');
        $response->assertOk();
        // Tidak ada tombol aksi return untuk tabung AVAILABLE
        // (link di sidebar tidak dihitung).
        $response->assertDontSee(route('transactions.return.create', ['cylinder' => $available->cylinder_code]), false);
    }

    public function test_histori_return_muncul_pada_detail(): void
    {
        $cylinder = $this->makeCylinder();
        $this->actingAs($this->operator)
            ->followingRedirects()
            ->post('/transactions/return', $this->validPayload($cylinder))
            ->assertSee('Tabung berhasil di-return.')
            ->assertSee('Riwayat Transaksi')
            ->assertSee('RETURN');
    }
}
