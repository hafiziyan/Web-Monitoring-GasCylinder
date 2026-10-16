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

class TransactionOutTest extends TestCase
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
            'serial_number' => 'SN-OUT-'.$counter,
            'gas_type_id' => $this->gas->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
            'qr_token' => 'CYL-OUTTEST'.$counter,
        ], $overrides));
    }

    private function validPayload(Cylinder $cylinder, array $overrides = []): array
    {
        return array_merge([
            'cylinder_id' => $cylinder->id,
            'destination' => 'PT ABC',
            'condition' => 'GOOD',
            'remaining_gas' => 6.00,
            'transaction_date' => '2026-10-02 10:00:00',
            'notes' => 'Keluar ke pelanggan.',
        ], $overrides);
    }

    public function test_guest_tidak_dapat_membuka_form_out(): void
    {
        $this->get('/transactions/out')->assertRedirect('/login');
        $this->post('/transactions/out', [])->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_form_out(): void
    {
        $this->actingAs($this->admin)->get('/transactions/out')->assertOk()->assertSee('Tabung Keluar');
    }

    public function test_operator_dapat_membuka_form_out(): void
    {
        $this->actingAs($this->operator)->get('/transactions/out')->assertOk()->assertSee('Tabung Keluar');
    }

    public function test_out_berhasil_untuk_available_dan_empty(): void
    {
        foreach (['AVAILABLE', 'EMPTY'] as $status) {
            $cylinder = $this->makeCylinder(['status' => $status]);

            $this->actingAs($this->operator)
                ->post('/transactions/out', $this->validPayload($cylinder))
                ->assertRedirect(route('cylinders.show', $cylinder));

            $this->assertDatabaseHas('transactions', [
                'cylinder_id' => $cylinder->id,
                'transaction_type' => 'OUT',
            ]);
        }
    }

    public function test_status_outside_lokasi_null_from_terisi_to_null(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'AVAILABLE']);

        $this->actingAs($this->operator)->post('/transactions/out', $this->validPayload($cylinder));

        $cylinder->refresh();
        $this->assertSame('OUTSIDE', $cylinder->status->value);
        $this->assertNull($cylinder->current_location_id);

        $trx = Transaction::where('cylinder_id', $cylinder->id)->firstOrFail();
        $this->assertSame($this->gudang->id, $trx->from_location_id);
        $this->assertNull($trx->to_location_id);
        $this->assertSame('PT ABC', $trx->destination);
        $this->assertSame($this->operator->id, $trx->user_id);
        $this->assertSame('OUT', $trx->transaction_type->value);
    }

    public function test_qr_token_dan_cylinder_code_tidak_berubah(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'qr_token' => 'CYL-TETAP']);

        $this->actingAs($this->operator)->post('/transactions/out', $this->validPayload($cylinder));

        $cylinder->refresh();
        $this->assertSame('GAS-00001', $cylinder->cylinder_code);
        $this->assertSame('CYL-TETAP', $cylinder->qr_token);
    }

    #[DataProvider('statusDitolakProvider')]
    public function test_out_ditolak_untuk_status_tidak_valid(string $status): void
    {
        $cylinder = $this->makeCylinder(['status' => $status]);

        $this->actingAs($this->operator)
            ->post('/transactions/out', $this->validPayload($cylinder))
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

    public function test_destination_wajib_diisi(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/out', $this->validPayload($cylinder, ['destination' => '']))
            ->assertSessionHasErrors('destination');

        $this->assertSame(0, Transaction::count());
    }

    public function test_remaining_gas_tidak_boleh_negatif(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/out', $this->validPayload($cylinder, ['remaining_gas' => -2]))
            ->assertSessionHasErrors('remaining_gas');

        $this->assertSame(0, Transaction::count());
    }

    public function test_kegagalan_tidak_menyimpan_sebagian(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/out', $this->validPayload($cylinder, ['cylinder_id' => 9999]))
            ->assertSessionHasErrors('cylinder_id');

        $this->assertSame(0, Transaction::count());
        $this->assertSame('AVAILABLE', $cylinder->refresh()->status->value);
        $this->assertSame($this->gudang->id, $cylinder->refresh()->current_location_id);
    }

    public function test_tombol_keluar_hanya_untuk_status_valid(): void
    {
        $valid = $this->makeCylinder(['status' => 'AVAILABLE', 'qr_token' => 'CYL-V1']);
        $invalid = $this->makeCylinder(['status' => 'MAINTENANCE', 'qr_token' => 'CYL-V2']);

        $this->actingAs($this->operator)
            ->get('/transactions/scan/CYL-V1')
            ->assertSee(route('transactions.out.create', ['cylinder' => $valid->cylinder_code]), false);

        $response = $this->actingAs($this->operator)->get('/transactions/scan/CYL-V2');
        $response->assertOk();
        $response->assertDontSee(route('transactions.out.create', ['cylinder' => $invalid->cylinder_code]), false);
    }
}
