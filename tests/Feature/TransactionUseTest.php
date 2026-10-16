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

class TransactionUseTest extends TestCase
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
            'serial_number' => 'SN-USE-'.$counter,
            'gas_type_id' => $this->gas->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
            'qr_token' => 'CYL-USETEST'.$counter,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'condition' => 'GOOD',
            'transaction_date' => '2026-10-04 09:00:00',
            'notes' => 'Dipakai produksi.',
        ], $overrides);
    }

    public function test_guest_tidak_dapat_membuka_form_use(): void
    {
        $this->get('/transactions/use-start')->assertRedirect('/login');
        $this->get('/transactions/use-end')->assertRedirect('/login');
        $this->post('/transactions/use-start', [])->assertRedirect('/login');
        $this->post('/transactions/use-end', [])->assertRedirect('/login');
    }

    public function test_operator_dapat_membuka_form_use(): void
    {
        $this->actingAs($this->operator)->get('/transactions/use-start')->assertOk()->assertSee('Mulai Pakai');
        $this->actingAs($this->operator)->get('/transactions/use-end')->assertOk()->assertSee('Selesai Pakai');
    }

    public function test_use_start_berhasil_untuk_available(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/use-start', $this->payload(['cylinder_id' => $cylinder->id]))
            ->assertRedirect(route('cylinders.show', $cylinder));

        $this->assertSame('IN_USE', $cylinder->refresh()->status->value);
        $this->assertSame('USE_START', Transaction::firstOrFail()->transaction_type->value);
    }

    #[DataProvider('bukanAvailableProvider')]
    public function test_use_start_ditolak_untuk_status_bukan_available(string $status): void
    {
        $cylinder = $this->makeCylinder([
            'status' => $status,
            'current_location_id' => $status === 'OUTSIDE' ? null : $this->gudang->id,
        ]);

        $this->actingAs($this->operator)
            ->post('/transactions/use-start', $this->payload(['cylinder_id' => $cylinder->id]))
            ->assertRedirect();

        $this->assertSame(0, Transaction::count());
        $this->assertSame($status, $cylinder->refresh()->status->value);
    }

    public static function bukanAvailableProvider(): array
    {
        return [
            'EMPTY' => ['EMPTY'],
            'IN_USE' => ['IN_USE'],
            'OUTSIDE' => ['OUTSIDE'],
            'MAINTENANCE' => ['MAINTENANCE'],
            'INACTIVE' => ['INACTIVE'],
        ];
    }

    public function test_use_end_tanpa_sisa_menjadi_empty(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'IN_USE']);

        $this->actingAs($this->operator)
            ->post('/transactions/use-end', $this->payload(['cylinder_id' => $cylinder->id]))
            ->assertRedirect(route('cylinders.show', $cylinder));

        $this->assertSame('EMPTY', $cylinder->refresh()->status->value);
    }

    public function test_use_end_dengan_sisa_gas_kembali_available(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'IN_USE']);

        $this->actingAs($this->operator)
            ->post('/transactions/use-end', $this->payload([
                'cylinder_id' => $cylinder->id,
                'remaining_gas' => 2.00,
            ]))->assertRedirect(route('cylinders.show', $cylinder));

        $this->assertSame('AVAILABLE', $cylinder->refresh()->status->value);
        $this->assertSame('2.00', Transaction::firstOrFail()->remaining_gas);
    }

    #[DataProvider('bukanInUseProvider')]
    public function test_use_end_ditolak_untuk_status_bukan_in_use(string $status): void
    {
        $cylinder = $this->makeCylinder([
            'status' => $status,
            'current_location_id' => $status === 'OUTSIDE' ? null : $this->gudang->id,
        ]);

        $this->actingAs($this->operator)
            ->post('/transactions/use-end', $this->payload(['cylinder_id' => $cylinder->id]))
            ->assertRedirect();

        $this->assertSame(0, Transaction::count());
        $this->assertSame($status, $cylinder->refresh()->status->value);
    }

    public static function bukanInUseProvider(): array
    {
        return [
            'AVAILABLE' => ['AVAILABLE'],
            'EMPTY' => ['EMPTY'],
            'OUTSIDE' => ['OUTSIDE'],
            'MAINTENANCE' => ['MAINTENANCE'],
            'INACTIVE' => ['INACTIVE'],
        ];
    }

    public function test_tanggal_masa_depan_ditolak(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->post('/transactions/use-start', $this->payload([
                'cylinder_id' => $cylinder->id,
                'transaction_date' => now()->addDay()->format('Y-m-d H:i:s'),
            ]))->assertSessionHasErrors('transaction_date');

        $this->assertSame(0, Transaction::count());
        $this->assertSame('AVAILABLE', $cylinder->refresh()->status->value);
    }

    public function test_sisa_gas_melebihi_kapasitas_ditolak(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'IN_USE']);

        $this->actingAs($this->operator)
            ->post('/transactions/use-end', $this->payload([
                'cylinder_id' => $cylinder->id,
                'remaining_gas' => 99.99,
            ]))->assertSessionHasErrors('remaining_gas');

        $this->assertSame(0, Transaction::count());
        $this->assertSame('IN_USE', $cylinder->refresh()->status->value);
    }

    public function test_pilihan_bertahan_setelah_penolakan_status(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'IN_USE']);

        // use-start ditolak di controller via back(): pilihan harus bertahan.
        $this->actingAs($this->operator)
            ->from('/transactions/use-start')
            ->post('/transactions/use-start', $this->payload(['cylinder_id' => $cylinder->id]))
            ->assertRedirect('/transactions/use-start')
            ->assertSessionHas('error');

        $this->actingAs($this->operator)
            ->get('/transactions/use-start')
            ->assertOk()
            ->assertSee('"cylinder_code":"'.$cylinder->cylinder_code.'"', false);
    }
}
