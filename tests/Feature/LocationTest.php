<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
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
        $this->get('/locations')->assertRedirect('/login');
    }

    public function test_admin_dapat_melihat(): void
    {
        Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);

        $this->actingAs($this->admin)->get('/locations')->assertOk()->assertSee('Gudang');
    }

    public function test_operator_dapat_melihat(): void
    {
        Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);

        $this->actingAs($this->operator)->get('/locations')->assertOk()->assertSee('Gudang');
    }

    public function test_admin_dapat_create(): void
    {
        $this->actingAs($this->admin)->post('/locations', [
            'code' => 'LOC-001',
            'name' => 'Gudang',
            'description' => 'Gudang utama.',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('locations', ['code' => 'LOC-001', 'name' => 'Gudang']);
    }

    public function test_admin_dapat_update(): void
    {
        $location = Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('locations.update', $location), [
            'code' => 'LOC-001',
            'name' => 'Gudang Baru',
        ])->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'Gudang Baru']);
    }

    public function test_operator_tidak_dapat_create_atau_update(): void
    {
        $location = Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);

        $this->actingAs($this->operator)->get('/locations/create')->assertForbidden();
        $this->actingAs($this->operator)->post('/locations', ['code' => 'LOC-002', 'name' => 'X'])->assertForbidden();
        $this->actingAs($this->operator)->put(route('locations.update', $location), ['code' => 'LOC-001', 'name' => 'X'])->assertForbidden();
        $this->actingAs($this->operator)->delete(route('locations.destroy', $location))->assertForbidden();
    }

    public function test_code_duplikat_ditolak(): void
    {
        Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->post('/locations', ['code' => 'LOC-001', 'name' => 'Lain'])
            ->assertSessionHasErrors('code');
    }

    public function test_location_yang_digunakan_cylinder_tidak_dapat_dihapus(): void
    {
        $location = Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);
        $gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        Cylinder::create([
            'cylinder_code' => 'GAS-00001',
            'gas_type_id' => $gas->id,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $location->id,
            'qr_token' => 'QR-1',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('locations.destroy', $location))
            ->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
    }

    public function test_location_yang_digunakan_transaksi_tidak_dapat_dihapus(): void
    {
        $gudang = Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);
        $lain = Location::create(['name' => 'Bengkel', 'code' => 'LOC-002', 'is_active' => true]);
        $gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $cylinder = Cylinder::create([
            'cylinder_code' => 'GAS-00001',
            'gas_type_id' => $gas->id,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $lain->id,
            'qr_token' => 'QR-1',
        ]);
        Transaction::create([
            'cylinder_id' => $cylinder->id,
            'user_id' => $this->admin->id,
            'transaction_type' => TransactionType::Transfer,
            'from_location_id' => $gudang->id,
            'to_location_id' => $lain->id,
            'transaction_date' => '2026-09-10 08:00:00',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('locations.destroy', $gudang))
            ->assertRedirect();

        $this->assertDatabaseHas('locations', ['id' => $gudang->id]);
    }
}
