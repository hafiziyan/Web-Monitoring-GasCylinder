<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanQrTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['role' => UserRole::Operator]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        $gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $location = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-00001',
            'serial_number' => 'SN-00001',
            'gas_type_id' => $gas->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'IN_USE',
            'condition' => 'GOOD',
            'current_location_id' => $location->id,
            'qr_token' => 'CYL-SCANTEST1',
        ], $overrides));
    }

    public function test_guest_tidak_dapat_membuka_halaman_scan(): void
    {
        $this->get('/transactions/scan')->assertRedirect('/login');
        $this->get('/transactions/scan/CYL-XXX')->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_halaman_scan(): void
    {
        $this->actingAs($this->admin)
            ->get('/transactions/scan')
            ->assertOk()
            ->assertSee('Scan QR Code')
            ->assertSee('Cari Tabung');
    }

    public function test_operator_dapat_membuka_halaman_scan(): void
    {
        $this->actingAs($this->operator)
            ->get('/transactions/scan')
            ->assertOk()
            ->assertSee('Scan QR Code');
    }

    public function test_qr_token_valid_menampilkan_detail_tabung_yang_benar(): void
    {
        $cylinder = $this->makeCylinder(['status' => 'AVAILABLE']);

        $this->actingAs($this->operator)
            ->get('/transactions/scan/CYL-SCANTEST1')
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee('SN-00001')
            ->assertSee('Oxygen')
            ->assertSee('AVAILABLE')
            ->assertSee('Gudang Gas')
            ->assertSee('CYL-SCANTEST1')
            ->assertSee('Tabung Masuk')
            ->assertSee('Maintenance')
            ->assertSee(route('transactions.maintenance.create', ['cylinder' => $cylinder->cylinder_code]), false);
    }

    public function test_tombol_maintenance_hanya_untuk_status_valid(): void
    {
        $outside = $this->makeCylinder(['status' => 'OUTSIDE', 'current_location_id' => null, 'qr_token' => 'CYL-SCAN-OUT']);

        $response = $this->actingAs($this->operator)->get('/transactions/scan/CYL-SCAN-OUT');
        $response->assertOk();
        $response->assertDontSee('>Maintenance<');
    }

    public function test_qr_token_tidak_valid_menampilkan_pesan(): void
    {
        $this->actingAs($this->operator)
            ->get('/transactions/scan/CYL-TIDAK-ADA')
            ->assertOk()
            ->assertSee('QR Code tidak terdaftar.')
            ->assertSee('Kembali ke Scan');
    }

    public function test_scan_tidak_mengubah_status_dan_lokasi_tabung(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)->get('/transactions/scan/CYL-SCANTEST1')->assertOk();

        $cylinder->refresh();
        $this->assertSame('IN_USE', $cylinder->status->value);
        $this->assertSame('Gudang Gas', $cylinder->currentLocation->name);
    }

    public function test_qr_token_tetap_sama_setelah_scan(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->admin)->get('/transactions/scan/CYL-SCANTEST1')->assertOk();

        $this->assertSame('CYL-SCANTEST1', $cylinder->refresh()->qr_token);
    }

    public function test_input_manual_mengarahkan_ke_hasil_scan(): void
    {
        $this->makeCylinder();

        $this->actingAs($this->operator)
            ->get('/transactions/scan?token=CYL-SCANTEST1')
            ->assertRedirect('/transactions/scan/CYL-SCANTEST1');
    }
}
