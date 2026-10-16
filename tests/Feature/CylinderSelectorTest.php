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

class CylinderSelectorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $gas;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->location = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-SEL-'.$counter,
            'gas_type_id' => $this->gas->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->location->id,
            'qr_token' => 'CYL-SEL'.$counter,
        ], $overrides));
    }

    public function test_guest_tidak_dapat_mengakses_search(): void
    {
        $this->get('/transactions/cylinders/search?q=GAS')->assertRedirect('/login');
    }

    public function test_search_berdasarkan_cylinder_code(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001']);

        $this->actingAs($this->operator)
            ->getJson('/transactions/cylinders/search?q=GAS-00001')
            ->assertOk()
            ->assertJsonFragment(['cylinder_code' => 'GAS-00001']);
    }

    public function test_search_berdasarkan_serial_number(): void
    {
        $this->makeCylinder(['serial_number' => 'SN-12345']);

        $this->actingAs($this->operator)
            ->getJson('/transactions/cylinders/search?q=SN-12345')
            ->assertOk()
            ->assertJsonFragment(['serial_number' => 'SN-12345']);
    }

    public function test_search_berdasarkan_qr_token(): void
    {
        $this->makeCylinder(['qr_token' => 'CYL-D8B6D7EC']);

        $this->actingAs($this->operator)
            ->getJson('/transactions/cylinders/search?q=CYL-D8B6D7EC')
            ->assertOk()
            ->assertJsonFragment(['qr_token' => 'CYL-D8B6D7EC']);
    }

    public function test_search_menampilkan_informasi_pembeda(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001']);

        $response = $this->actingAs($this->operator)
            ->getJson('/transactions/cylinders/search?q=GAS-00001')
            ->assertOk();

        $item = $response->json()[0];
        $this->assertSame('Oxygen', $item['gas_type']);
        $this->assertSame('AVAILABLE', $item['status']);
        $this->assertSame('Gudang Gas', $item['current_location']);
        $this->assertArrayHasKey('serial_number', $item);
        $this->assertArrayHasKey('condition', $item);
        $this->assertArrayNotHasKey('user', $item);
    }

    public function test_search_maksimal_10_hasil(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->makeCylinder();
        }

        $response = $this->actingAs($this->operator)
            ->getJson('/transactions/cylinders/search?q=GAS-')
            ->assertOk();

        $this->assertCount(10, $response->json());
    }

    public function test_cylinder_dapat_dipilih_dan_detail_tampil(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'serial_number' => 'SN-12345', 'qr_token' => 'CYL-D8B6D7EC']);

        $this->actingAs($this->operator)
            ->get('/transactions/in?cylinder=GAS-00001')
            ->assertOk()
            ->assertSee('Pilih Tabung')
            ->assertSee('GAS-00001')
            ->assertSee('SN-12345')
            ->assertSee('Oxygen')
            ->assertSee('CYL-D8B6D7EC')
            ->assertSee('Ganti Tabung')
            ->assertSee('"cylinder_code":"GAS-00001"', false);
    }

    public function test_qr_token_valid_otomatis_memilih_cylinder(): void
    {
        $this->makeCylinder(['qr_token' => 'CYL-D8B6D7EC']);

        $this->actingAs($this->operator)
            ->get('/transactions/out?cylinder=CYL-D8B6D7EC')
            ->assertOk()
            ->assertSee('Pilih Tabung')
            ->assertSee('CYL-D8B6D7EC');
    }

    public function test_qr_token_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->operator)
            ->get('/transactions/out?cylinder=CYL-TIDAK-ADA')
            ->assertOk()
            ->assertSee('Tabung tidak ditemukan.')
            ->assertSee('initial: null', false);
    }

    public function test_search_mendukung_filter_status_out(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'status' => 'AVAILABLE']);
        $this->makeCylinder(['cylinder_code' => 'GAS-00002', 'status' => 'MAINTENANCE']);

        $codes = collect($this->actingAs($this->operator)
            ->getJson('/transactions/cylinders/search?q=GAS-&status=AVAILABLE,EMPTY')
            ->assertOk()
            ->json())->pluck('cylinder_code')->all();

        $this->assertContains('GAS-00001', $codes);
        $this->assertNotContains('GAS-00002', $codes);
    }

    public function test_search_mendukung_filter_status_return(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'status' => 'OUTSIDE', 'current_location_id' => null]);
        $this->makeCylinder(['cylinder_code' => 'GAS-00002', 'status' => 'AVAILABLE']);

        $codes = collect($this->actingAs($this->operator)
            ->getJson('/transactions/cylinders/search?q=GAS-&status=OUTSIDE')
            ->assertOk()
            ->json())->pluck('cylinder_code')->all();

        $this->assertContains('GAS-00001', $codes);
        $this->assertNotContains('GAS-00002', $codes);
    }

    public function test_search_mendukung_filter_status_transfer(): void
    {
        // TRANSFER memakai filter yang sama dengan OUT: AVAILABLE/EMPTY.
        $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'status' => 'EMPTY']);
        $this->makeCylinder(['cylinder_code' => 'GAS-00002', 'status' => 'OUTSIDE', 'current_location_id' => null]);

        $codes = collect($this->actingAs($this->operator)
            ->getJson('/transactions/cylinders/search?q=GAS-&status=AVAILABLE,EMPTY')
            ->assertOk()
            ->json())->pluck('cylinder_code')->all();

        $this->assertContains('GAS-00001', $codes);
        $this->assertNotContains('GAS-00002', $codes);
    }

    public function test_backend_menolak_status_tidak_valid_meski_frontend_dimanipulasi(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'MAINTENANCE']);

        $this->actingAs($this->operator)->post('/transactions/out', [
            'cylinder_id' => $cylinder->id,
            'destination' => 'PT ABC',
            'condition' => 'GOOD',
            'transaction_date' => '2026-10-02 10:00:00',
        ])->assertRedirect();

        $this->assertSame(0, Transaction::count());
        $this->assertSame('MAINTENANCE', $cylinder->refresh()->status->value);
    }

    public function test_status_dicek_ulang_saat_submit(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'AVAILABLE']);

        // Status berubah setelah form dibuka, sebelum submit.
        $cylinder->update(['status' => 'IN_USE']);

        $this->actingAs($this->operator)->post('/transactions/out', [
            'cylinder_id' => $cylinder->id,
            'destination' => 'PT ABC',
            'condition' => 'GOOD',
            'transaction_date' => '2026-10-02 10:00:00',
        ])->assertRedirect();

        $this->assertSame(0, Transaction::count());
        $this->assertSame('IN_USE', $cylinder->refresh()->status->value);
    }

    public function test_semua_form_menampilkan_selector_lengkap(): void
    {
        $urls = [
            '/transactions/in',
            '/transactions/out',
            '/transactions/return',
            '/transactions/transfer',
            '/transactions/maintenance',
            '/transactions/maintenance/complete',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->operator)->get($url);
            $response->assertOk();
            $response->assertSee('Scan QR');
            $response->assertSee('Cari kode tabung, serial number, atau QR token...', false);
            $response->assertSee('Pilih Tabung dari Daftar');
            $response->assertSee('aria-label="Scan QR Code"', false);
            $response->assertSee('masukkan QR Token manual');
            $response->assertSee('Pilih Kamera');
        }
    }

    public function test_tidak_ada_atau_menggantung_tanpa_opsi(): void
    {
        $response = $this->actingAs($this->operator)->get('/transactions/out');
        $response->assertOk();
        // Setiap pemisah "atau" harus diikuti opsi berikutnya pada halaman.
        $response->assertSee('atau cari tabung', false);
        $response->assertSee('atau pilih dari daftar', false);
    }
}
