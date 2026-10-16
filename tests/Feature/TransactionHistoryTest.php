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

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $oxygen;

    private GasType $argon;

    private Location $gudang;

    private Location $welding;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Admin Riwayat', 'role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['name' => 'Operator Riwayat', 'role' => UserRole::Operator]);
        $this->oxygen = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->argon = GasType::create(['name' => 'Argon', 'code' => 'AR', 'is_active' => true]);
        $this->gudang = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
        $this->welding = Location::create(['name' => 'Welding Area', 'code' => 'LOC-002', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-HIST-'.$counter,
            'gas_type_id' => $this->oxygen->id,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
            'qr_token' => 'CYL-HIST'.$counter,
        ], $overrides));
    }

    private function makeTransaction(Cylinder $cylinder, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'cylinder_id' => $cylinder->id,
            'user_id' => $this->operator->id,
            'transaction_type' => 'OUT',
            'from_location_id' => $this->gudang->id,
            'to_location_id' => null,
            'condition' => 'GOOD',
            'remaining_gas' => 6.00,
            'destination' => 'PT ABC',
            'transaction_date' => '2026-10-05 10:00:00',
        ], $overrides));
    }

    public function test_guest_tidak_dapat_membuka_riwayat(): void
    {
        $this->get('/transactions')->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_riwayat(): void
    {
        $this->actingAs($this->admin)->get('/transactions')->assertOk()->assertSee('Riwayat Transaksi');
    }

    public function test_operator_dapat_membuka_riwayat(): void
    {
        $this->actingAs($this->operator)->get('/transactions')->assertOk()->assertSee('Riwayat Transaksi');
    }

    public function test_riwayat_menampilkan_transaksi_dari_database(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $this->makeTransaction($cylinder);

        $this->actingAs($this->admin)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee('TABUNG KELUAR')
            ->assertSee('Operator Riwayat');
    }

    public function test_search_berdasarkan_cylinder_code_serial_dan_token(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'serial_number' => 'SN-UNIK', 'qr_token' => 'CYL-TOKENUNIK']);
        $this->makeTransaction($cylinder);
        $other = $this->makeCylinder(['cylinder_code' => 'GAS-00002']);
        $this->makeTransaction($other);

        foreach (['GAS-00001', 'SN-UNIK', 'CYL-TOKENUNIK'] as $keyword) {
            $response = $this->actingAs($this->admin)->get('/transactions?search='.urlencode($keyword));
            $response->assertOk();
            $response->assertSee('GAS-00001');
            $response->assertDontSee('GAS-00002');
        }
    }

    public function test_filter_berdasarkan_transaction_type(): void
    {
        $cylinder = $this->makeCylinder();
        $this->makeTransaction($cylinder, ['transaction_type' => 'IN', 'to_location_id' => $this->gudang->id]);
        $this->makeTransaction($cylinder, ['transaction_type' => 'OUT']);

        $response = $this->actingAs($this->admin)->get('/transactions?type=OUT');
        $response->assertOk();
        $response->assertSee('TABUNG KELUAR');
        $response->assertDontSee('TABUNG MASUK');
    }

    public function test_filter_berdasarkan_gas_type(): void
    {
        $oxy = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'gas_type_id' => $this->oxygen->id]);
        $this->makeTransaction($oxy);
        $arg = $this->makeCylinder(['cylinder_code' => 'GAS-00002', 'gas_type_id' => $this->argon->id]);
        $this->makeTransaction($arg);

        $response = $this->actingAs($this->admin)->get('/transactions?gas_type_id='.$this->argon->id);
        $response->assertOk();
        $response->assertSee('GAS-00002');
        $response->assertDontSee('GAS-00001');
    }

    public function test_filter_tanggal_dari(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $this->makeTransaction($cylinder, ['transaction_date' => '2026-10-01 08:00:00']);
        $this->makeTransaction($cylinder, ['cylinder_id' => $cylinder->id, 'transaction_date' => '2026-10-05 08:00:00']);

        $response = $this->actingAs($this->admin)->get('/transactions?date_from=2026-10-03');
        $response->assertOk();
        // Hanya 1 baris transaksi (tanggal 5 Okt).
        $this->assertSame(1, substr_count($response->getContent(), 'GAS-00001'));
    }

    public function test_filter_tanggal_sampai_mencakup_sehari_penuh(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $this->makeTransaction($cylinder, ['transaction_date' => '2026-10-06 23:59:59']);

        $this->actingAs($this->admin)
            ->get('/transactions?date_to=2026-10-06')
            ->assertOk()
            ->assertSee('GAS-00001');
    }

    public function test_filter_lokasi_asal_dan_tujuan(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $this->makeTransaction($cylinder, ['from_location_id' => $this->gudang->id, 'to_location_id' => $this->welding->id]);

        $this->actingAs($this->admin)
            ->get('/transactions?from_location_id='.$this->gudang->id)
            ->assertOk()
            ->assertSee('GAS-00001');

        $this->actingAs($this->admin)
            ->get('/transactions?from_location_id='.$this->welding->id)
            ->assertOk()
            ->assertDontSee('GAS-00001');

        $this->actingAs($this->admin)
            ->get('/transactions?to_location_id='.$this->welding->id)
            ->assertOk()
            ->assertSee('GAS-00001');
    }

    public function test_reset_filter_bekerja(): void
    {
        $this->actingAs($this->admin)
            ->get('/transactions?type=OUT')
            ->assertOk()
            ->assertSee('Reset Filter')
            ->assertSee(route('transactions.index'), false);
    }

    public function test_pagination_bekerja_dan_filter_terbawa(): void
    {
        $cylinder = $this->makeCylinder();
        for ($i = 0; $i < 21; $i++) {
            $this->makeTransaction($cylinder, ['transaction_date' => '2026-10-05 10:'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).':00']);
        }

        $page1 = $this->actingAs($this->admin)->get('/transactions?type=OUT');
        $page1->assertOk();
        // Default per_page aplikasi adalah 10: header + 10 baris di halaman 1.
        $this->assertSame(11, substr_count($page1->getContent(), '<tr'));

        $page2 = $this->actingAs($this->admin)->get('/transactions?type=OUT&page=2');
        $page2->assertOk();
        $this->assertSame(11, substr_count($page2->getContent(), '<tr'));
        // Filter tetap terbawa pada link pagination.
        $page2->assertSee('type=OUT', false);
    }

    public function test_detail_transaksi_dapat_dibuka_dengan_relasi_benar(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $trx = $this->makeTransaction($cylinder);

        $this->actingAs($this->operator)
            ->get(route('transactions.show', $trx))
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee('Oxygen')
            ->assertSee('Operator Riwayat')
            ->assertSee('Gudang Gas')
            ->assertSee('PT ABC')
            ->assertSee(route('cylinders.show', $cylinder), false);
    }

    public function test_tidak_tersedia_endpoint_edit_atau_delete_transaksi(): void
    {
        $cylinder = $this->makeCylinder();
        $trx = $this->makeTransaction($cylinder);

        foreach (['put', 'patch', 'delete'] as $method) {
            $this->actingAs($this->operator)->{$method}('/transactions/'.$trx->id)->assertStatus(405);
            $this->actingAs($this->admin)->{$method}('/transactions/'.$trx->id)->assertStatus(405);
        }
        $this->actingAs($this->operator)->post('/transactions')->assertStatus(405);

        $this->assertDatabaseHas('transactions', ['id' => $trx->id]);
    }

    public function test_empty_state_tanpa_transaksi(): void
    {
        $this->actingAs($this->admin)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Belum ada transaksi.');
    }

    public function test_empty_state_filter_kosong(): void
    {
        $cylinder = $this->makeCylinder();
        $this->makeTransaction($cylinder);

        $this->actingAs($this->admin)
            ->get('/transactions?search=TIDAK-ADA')
            ->assertOk()
            ->assertSee('Tidak ada transaksi yang sesuai dengan filter.');
    }
}
