<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionInTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $gas;

    private Location $gudang;

    private Location $bengkel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->gudang = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
        $this->bengkel = Location::create(['name' => 'Bengkel', 'code' => 'LOC-002', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-IN-'.$counter,
            'gas_type_id' => $this->gas->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'EMPTY',
            'condition' => 'GOOD',
            'current_location_id' => $this->bengkel->id,
            'qr_token' => 'CYL-INTEST'.$counter,
        ], $overrides));
    }

    private function validPayload(Cylinder $cylinder, array $overrides = []): array
    {
        return array_merge([
            'cylinder_id' => $cylinder->id,
            'to_location_id' => $this->gudang->id,
            'condition' => 'GOOD',
            'remaining_gas' => 6.00,
            'transaction_date' => '2026-10-01 09:00:00',
            'notes' => 'Masuk gudang.',
        ], $overrides);
    }

    public function test_guest_tidak_dapat_melakukan_transaksi_in(): void
    {
        $this->get('/transactions/in')->assertRedirect('/login');
        $this->post('/transactions/in', [])->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_form_in(): void
    {
        $this->actingAs($this->admin)->get('/transactions/in')->assertOk()->assertSee('Tabung Masuk');
    }

    public function test_operator_dapat_membuka_form_in(): void
    {
        $this->actingAs($this->operator)->get('/transactions/in')->assertOk()->assertSee('Tabung Masuk');
    }

    public function test_transaksi_in_berhasil_membuat_record(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/in', $this->validPayload($cylinder))
            ->assertRedirect(route('cylinders.show', $cylinder));

        $this->assertDatabaseHas('transactions', [
            'cylinder_id' => $cylinder->id,
            'user_id' => $this->operator->id,
            'transaction_type' => 'IN',
            'to_location_id' => $this->gudang->id,
        ]);
    }

    public function test_status_berubah_menjadi_available_dan_lokasi_berubah(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'EMPTY', 'condition' => 'DAMAGED']);

        $this->actingAs($this->admin)->post('/transactions/in', $this->validPayload($cylinder));

        $cylinder->refresh();
        $this->assertSame('AVAILABLE', $cylinder->status->value);
        $this->assertSame($this->gudang->id, $cylinder->current_location_id);
        $this->assertSame('GOOD', $cylinder->condition->value);
    }

    public function test_qr_token_dan_cylinder_code_tidak_berubah(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'qr_token' => 'CYL-TETAP']);

        $this->actingAs($this->operator)->post('/transactions/in', $this->validPayload($cylinder));

        $cylinder->refresh();
        $this->assertSame('GAS-00001', $cylinder->cylinder_code);
        $this->assertSame('CYL-TETAP', $cylinder->qr_token);
    }

    public function test_user_pelaksana_tercatat_dan_tipe_in(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)->post('/transactions/in', $this->validPayload($cylinder));

        $trx = Transaction::where('cylinder_id', $cylinder->id)->firstOrFail();
        $this->assertSame($this->operator->id, $trx->user_id);
        $this->assertSame('IN', $trx->transaction_type->value);
        $this->assertNull($trx->from_location_id);
        $this->assertNull($trx->destination);
    }

    public function test_transaksi_gagal_jika_lokasi_tidak_valid(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/in', $this->validPayload($cylinder, ['to_location_id' => 9999]))
            ->assertSessionHasErrors('to_location_id');

        $this->assertSame(0, Transaction::count());
        $this->assertSame('EMPTY', $cylinder->refresh()->status->value);
    }

    public function test_transaksi_gagal_jika_remaining_gas_negatif(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/in', $this->validPayload($cylinder, ['remaining_gas' => -1]))
            ->assertSessionHasErrors('remaining_gas');

        $this->assertSame(0, Transaction::count());
    }

    public function test_transaksi_gagal_untuk_cylinder_inactive(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'INACTIVE']);

        $this->actingAs($this->admin)
            ->post('/transactions/in', $this->validPayload($cylinder))
            ->assertRedirect();

        $this->assertSame(0, Transaction::count());
        $this->assertSame('INACTIVE', $cylinder->refresh()->status->value);
    }

    public function test_kegagalan_tidak_menyimpan_transaksi_sebagian(): void
    {
        $cylinder = $this->makeCylinder();

        // Cylinder tidak valid -> validasi menolak sebelum ada tulisan ke DB.
        $this->actingAs($this->operator)
            ->post('/transactions/in', $this->validPayload($cylinder, ['cylinder_id' => 9999]))
            ->assertSessionHasErrors('cylinder_id');

        $this->assertSame(0, Transaction::count());
        $this->assertSame($this->bengkel->id, $cylinder->refresh()->current_location_id);
    }

    public function test_histori_transaksi_tidak_dapat_dihapus(): void
    {
        $cylinder = $this->makeCylinder();
        $this->actingAs($this->operator)->post('/transactions/in', $this->validPayload($cylinder));
        $trx = Transaction::where('cylinder_id', $cylinder->id)->firstOrFail();

        // Tidak ada route hapus transaksi: harus 405 (route detail hanya GET).
        $this->actingAs($this->admin)->delete('/transactions/'.$trx->id)->assertStatus(405);
        $this->assertDatabaseHas('transactions', ['id' => $trx->id]);
    }

    public function test_detail_cylinder_menampilkan_riwayat_in(): void
    {
        $cylinder = $this->makeCylinder();
        $this->actingAs($this->operator)
            ->followingRedirects()
            ->post('/transactions/in', $this->validPayload($cylinder))
            ->assertSee('Transaksi Tabung Masuk berhasil disimpan.');

        $this->actingAs($this->operator)
            ->get(route('cylinders.show', $cylinder))
            ->assertOk()
            ->assertSee('Riwayat Transaksi')
            ->assertSee('IN');
    }
}
