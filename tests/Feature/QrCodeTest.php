<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\User;
use App\Services\CylinderQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeTest extends TestCase
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
            'serial_number' => 'SN-QR-'.$counter,
            'gas_type_id' => $this->gas->id,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->location->id,
            'qr_token' => 'CYL-QRTEST'.$counter,
        ], $overrides));
    }

    public function test_guest_tidak_dapat_membuka_qr_codes(): void
    {
        $this->get('/qr-codes')->assertRedirect('/login');
    }

    public function test_operator_tidak_dapat_membuka_area_qr(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)->get('/qr-codes')->assertForbidden();
        $this->actingAs($this->operator)->get(route('qr-codes.preview', $cylinder))->assertForbidden();
        $this->actingAs($this->operator)->get(route('qr-codes.print', $cylinder))->assertForbidden();
        $this->actingAs($this->operator)->post(route('qr-codes.generate', $cylinder))->assertForbidden();
    }

    public function test_admin_dapat_membuka_qr_codes(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001']);

        $this->actingAs($this->admin)
            ->get('/qr-codes')
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee('Generated');
    }

    public function test_qr_token_unik_dan_tidak_berubah_saat_halaman_dibuka(): void
    {
        $a = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $b = $this->makeCylinder(['cylinder_code' => 'GAS-00002']);

        $this->assertNotEmpty($a->qr_token);
        $this->assertNotSame($a->qr_token, $b->qr_token);

        $this->actingAs($this->admin)->get('/qr-codes')->assertOk();
        $this->actingAs($this->admin)->get(route('qr-codes.preview', $a))->assertOk();

        $this->assertSame($a->qr_token, $a->refresh()->qr_token);
    }

    public function test_admin_dapat_generate_token_untuk_cylinder_tanpa_token(): void
    {
        $cylinder = $this->makeCylinder(['qr_token' => null]);
        $this->assertNull($cylinder->qr_token);

        $this->actingAs($this->admin)
            ->post(route('qr-codes.generate', $cylinder))
            ->assertRedirect();

        $token = $cylinder->refresh()->qr_token;
        $this->assertNotEmpty($token);
        $this->assertStringStartsWith('CYL-', $token);
    }

    public function test_generate_tidak_mengganti_token_yang_sudah_ada(): void
    {
        $cylinder = $this->makeCylinder(['qr_token' => 'CYL-TETAP123']);

        $this->actingAs($this->admin)
            ->post(route('qr-codes.generate', $cylinder))
            ->assertRedirect();

        $this->assertSame('CYL-TETAP123', $cylinder->refresh()->qr_token);
    }

    public function test_admin_dapat_membuka_preview_qr(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);

        $this->actingAs($this->admin)
            ->get(route('qr-codes.preview', $cylinder))
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee('Oxygen')
            ->assertSee('<svg', false);
    }

    public function test_admin_dapat_membuka_halaman_print_satu_cylinder(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);

        $this->actingAs($this->admin)
            ->get(route('qr-codes.print', $cylinder))
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee('GAS CYLINDER')
            ->assertSee('<svg', false);
    }

    public function test_admin_dapat_membuka_halaman_print_multiple(): void
    {
        $a = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $b = $this->makeCylinder(['cylinder_code' => 'GAS-00003']);

        $this->actingAs($this->admin)
            ->get(route('qr-codes.print-selected', ['ids' => [$a->id, $b->id]]))
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee('GAS-00003');
    }

    public function test_qr_digenerate_lokal_tanpa_api_eksternal(): void
    {
        $svg = app(CylinderQrCode::class)->svg('CYL-8F42A91D');

        $this->assertStringContainsString('<svg', $svg);
    }
}
