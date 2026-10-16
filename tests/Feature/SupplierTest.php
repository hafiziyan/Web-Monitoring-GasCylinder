<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
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
        $this->get('/suppliers')->assertRedirect('/login');
    }

    public function test_admin_dapat_melihat(): void
    {
        Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);

        $this->actingAs($this->admin)->get('/suppliers')->assertOk()->assertSee('PT Gas');
    }

    public function test_operator_dapat_melihat(): void
    {
        Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);

        $this->actingAs($this->operator)->get('/suppliers')->assertOk()->assertSee('PT Gas');
    }

    public function test_admin_dapat_create(): void
    {
        $this->actingAs($this->admin)->post('/suppliers', [
            'code' => 'SUP-001',
            'name' => 'PT Gas',
            'contact_person' => 'Budi',
            'phone' => '021-123',
            'address' => 'Jakarta',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-001', 'contact_person' => 'Budi']);
    }

    public function test_admin_dapat_update(): void
    {
        $supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('suppliers.update', $supplier), [
            'code' => 'SUP-001',
            'name' => 'PT Gas Baru',
        ])->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'PT Gas Baru']);
    }

    public function test_operator_tidak_dapat_create_atau_update(): void
    {
        $supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);

        $this->actingAs($this->operator)->get('/suppliers/create')->assertForbidden();
        $this->actingAs($this->operator)->post('/suppliers', ['code' => 'SUP-002', 'name' => 'X'])->assertForbidden();
        $this->actingAs($this->operator)->put(route('suppliers.update', $supplier), ['code' => 'SUP-001', 'name' => 'X'])->assertForbidden();
        $this->actingAs($this->operator)->delete(route('suppliers.destroy', $supplier))->assertForbidden();
    }

    public function test_code_duplikat_ditolak(): void
    {
        Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->post('/suppliers', ['code' => 'SUP-001', 'name' => 'Lain'])
            ->assertSessionHasErrors('code');
    }

    public function test_supplier_yang_digunakan_tidak_dapat_dihapus(): void
    {
        $supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);
        $gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $location = Location::create(['name' => 'Gudang', 'code' => 'LOC-001', 'is_active' => true]);
        Cylinder::create([
            'cylinder_code' => 'GAS-00001',
            'gas_type_id' => $gas->id,
            'supplier_id' => $supplier->id,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $location->id,
            'qr_token' => 'QR-1',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
