<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GasTypeTest extends TestCase
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

    public function test_guest_ditolak(): void
    {
        $this->get('/gas-types')->assertRedirect('/login');
    }

    public function test_admin_dapat_melihat(): void
    {
        GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);

        $this->actingAs($this->admin)->get('/gas-types')->assertOk()->assertSee('Oxygen');
    }

    public function test_operator_dapat_melihat(): void
    {
        GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);

        $this->actingAs($this->operator)->get('/gas-types')->assertOk()->assertSee('Oxygen');
    }

    public function test_admin_dapat_create_dengan_code_uppercase(): void
    {
        $this->actingAs($this->admin)->post('/gas-types', [
            'code' => 'o2',
            'name' => 'Oxygen',
            'description' => 'Gas las.',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('gas_types', ['code' => 'O2', 'name' => 'Oxygen']);
    }

    public function test_admin_dapat_update(): void
    {
        $gasType = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('gas-types.update', $gasType), [
            'code' => 'O2',
            'name' => 'Oxygen Updated',
        ])->assertRedirect();

        $this->assertDatabaseHas('gas_types', ['id' => $gasType->id, 'name' => 'Oxygen Updated', 'is_active' => false]);
    }

    public function test_operator_tidak_dapat_create_atau_update(): void
    {
        $gasType = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);

        $this->actingAs($this->operator)->get('/gas-types/create')->assertForbidden();
        $this->actingAs($this->operator)->post('/gas-types', ['code' => 'AR', 'name' => 'Argon'])->assertForbidden();
        $this->actingAs($this->operator)->put(route('gas-types.update', $gasType), ['code' => 'O2', 'name' => 'X'])->assertForbidden();
        $this->actingAs($this->operator)->delete(route('gas-types.destroy', $gasType))->assertForbidden();
    }

    public function test_code_duplikat_ditolak(): void
    {
        GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->post('/gas-types', ['code' => 'O2', 'name' => 'Oksigen'])
            ->assertSessionHasErrors('code');
    }

    public function test_gas_type_yang_digunakan_tidak_dapat_dihapus(): void
    {
        $gasType = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $location = Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);
        Cylinder::create([
            'cylinder_code' => 'GAS-00001',
            'gas_type_id' => $gasType->id,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $location->id,
            'qr_token' => 'QR-1',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('gas-types.destroy', $gasType))
            ->assertRedirect();

        $this->assertDatabaseHas('gas_types', ['id' => $gasType->id]);
    }

    public function test_gas_type_tanpa_tabung_dapat_dihapus(): void
    {
        $gasType = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->delete(route('gas-types.destroy', $gasType))
            ->assertRedirect(route('gas-types.index'));

        $this->assertDatabaseMissing('gas_types', ['id' => $gasType->id]);
    }
}
