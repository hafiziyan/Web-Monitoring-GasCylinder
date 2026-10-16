<?php

namespace Tests\Feature;

use App\Enums\CylinderCondition;
use App\Enums\CylinderStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CylinderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $gas;

    private Supplier $supplier;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);
        $this->location = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-TEST-'.$counter,
            'gas_type_id' => $this->gas->id,
            'supplier_id' => $this->supplier->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => CylinderStatus::Available,
            'condition' => CylinderCondition::Good,
            'current_location_id' => $this->location->id,
            'qr_token' => 'QR-TEST-'.$counter,
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'cylinder_code' => 'GAS-00001',
            'serial_number' => 'SN-00001',
            'gas_type_id' => $this->gas->id,
            'supplier_id' => $this->supplier->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => CylinderStatus::Available->value,
            'condition' => CylinderCondition::Good->value,
            'current_location_id' => $this->location->id,
            'notes' => 'Tabung baru.',
        ], $overrides);
    }

    public function test_guest_tidak_dapat_membuka_cylinders(): void
    {
        $this->get('/cylinders')->assertRedirect('/login');
    }

    public function test_admin_dapat_melihat_daftar(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);

        $this->actingAs($this->admin)
            ->get('/cylinders')
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee($cylinder->serial_number);
    }

    public function test_admin_dapat_membuka_form_create(): void
    {
        $this->actingAs($this->admin)->get('/cylinders/create')->assertOk();
    }

    public function test_admin_dapat_membuat_cylinder_dengan_qr_otomatis(): void
    {
        $response = $this->actingAs($this->admin)->post('/cylinders', $this->validPayload());

        $cylinder = Cylinder::where('cylinder_code', 'GAS-00001')->firstOrFail();
        $response->assertRedirect(route('cylinders.show', $cylinder));
        $this->assertNotEmpty($cylinder->qr_token);
        $this->assertStringStartsWith('CYL-', $cylinder->qr_token);
    }

    public function test_admin_dapat_membuka_detail_beserta_riwayat(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        Transaction::create([
            'cylinder_id' => $cylinder->id,
            'user_id' => $this->admin->id,
            'transaction_type' => TransactionType::In,
            'to_location_id' => $this->location->id,
            'condition' => 'GOOD',
            'transaction_date' => '2026-09-10 08:00:00',
        ]);

        $this->actingAs($this->admin)
            ->get(route('cylinders.show', $cylinder))
            ->assertOk()
            ->assertSee('GAS-00001')
            ->assertSee($cylinder->qr_token)
            ->assertSee('Riwayat Transaksi')
            ->assertSee('IN');
    }

    public function test_admin_dapat_mengedit_cylinder_tanpa_mengubah_identitas(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'qr_token' => 'CYL-IDENTITAS']);

        $this->actingAs($this->admin)->get(route('cylinders.edit', $cylinder))->assertOk();

        $this->actingAs($this->admin)->put(route('cylinders.update', $cylinder), [
            'cylinder_code' => 'GAS-DIUBAH',
            'qr_token' => 'CYL-DIUBAH',
            'gas_type_id' => $this->gas->id,
            'capacity' => 7.50,
            'unit' => 'm3',
            'status' => CylinderStatus::InUse->value,
            'condition' => CylinderCondition::Good->value,
            'current_location_id' => $this->location->id,
        ])->assertRedirect(route('cylinders.show', $cylinder));

        $cylinder->refresh();
        $this->assertSame('GAS-00001', $cylinder->cylinder_code);
        $this->assertSame('CYL-IDENTITAS', $cylinder->qr_token);
        $this->assertSame('7.50', $cylinder->capacity);
    }

    public function test_admin_dapat_menghapus_cylinder_tanpa_transaksi(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->admin)
            ->delete(route('cylinders.destroy', $cylinder))
            ->assertRedirect(route('cylinders.index'));

        $this->assertDatabaseMissing('cylinders', ['id' => $cylinder->id]);
    }

    public function test_operator_dapat_melihat_daftar_dan_detail(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);

        $this->actingAs($this->operator)->get('/cylinders')->assertOk()->assertSee('GAS-00001');
        $this->actingAs($this->operator)->get(route('cylinders.show', $cylinder))->assertOk();
    }

    public function test_operator_tidak_dapat_membuka_create(): void
    {
        $this->actingAs($this->operator)->get('/cylinders/create')->assertForbidden();
    }

    public function test_operator_tidak_dapat_menyimpan_cylinder(): void
    {
        $this->actingAs($this->operator)
            ->post('/cylinders', $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('cylinders', ['cylinder_code' => 'GAS-00001']);
    }

    public function test_operator_tidak_dapat_mengedit_atau_update(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)->get(route('cylinders.edit', $cylinder))->assertForbidden();
        $this->actingAs($this->operator)->put(route('cylinders.update', $cylinder), [
            'gas_type_id' => $this->gas->id,
            'unit' => 'm3',
            'status' => CylinderStatus::Empty->value,
            'condition' => CylinderCondition::Good->value,
            'current_location_id' => $this->location->id,
        ])->assertForbidden();
    }

    public function test_operator_tidak_dapat_menghapus(): void
    {
        $cylinder = $this->makeCylinder();

        $this->actingAs($this->operator)
            ->delete(route('cylinders.destroy', $cylinder))
            ->assertForbidden();

        $this->assertDatabaseHas('cylinders', ['id' => $cylinder->id]);
    }

    public function test_cylinder_code_wajib_unik(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001']);

        $this->actingAs($this->admin)
            ->post('/cylinders', $this->validPayload(['cylinder_code' => 'GAS-00001']))
            ->assertSessionHasErrors('cylinder_code');
    }

    public function test_serial_number_tidak_boleh_duplikat(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'serial_number' => 'SN-SAMA']);

        $this->actingAs($this->admin)
            ->post('/cylinders', $this->validPayload(['cylinder_code' => 'GAS-00002', 'serial_number' => 'SN-SAMA']))
            ->assertSessionHasErrors('serial_number');
    }

    public function test_gas_type_dan_lokasi_harus_valid(): void
    {
        $this->actingAs($this->admin)
            ->post('/cylinders', $this->validPayload(['gas_type_id' => 9999, 'current_location_id' => 9999]))
            ->assertSessionHasErrors(['gas_type_id', 'current_location_id']);
    }

    public function test_delete_ditolak_jika_sudah_ada_transaksi(): void
    {
        $cylinder = $this->makeCylinder();
        Transaction::create([
            'cylinder_id' => $cylinder->id,
            'user_id' => $this->admin->id,
            'transaction_type' => TransactionType::In,
            'to_location_id' => $this->location->id,
            'transaction_date' => '2026-09-10 08:00:00',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('cylinders.destroy', $cylinder))
            ->assertRedirect();

        $this->assertDatabaseHas('cylinders', ['id' => $cylinder->id]);
    }

    public function test_delete_ditolak_jika_status_in_use_atau_outside(): void
    {
        $inUse = $this->makeCylinder(['status' => CylinderStatus::InUse]);
        $outside = $this->makeCylinder(['status' => CylinderStatus::Outside]);

        $this->actingAs($this->admin)->delete(route('cylinders.destroy', $inUse))->assertRedirect();
        $this->actingAs($this->admin)->delete(route('cylinders.destroy', $outside))->assertRedirect();

        $this->assertDatabaseHas('cylinders', ['id' => $inUse->id]);
        $this->assertDatabaseHas('cylinders', ['id' => $outside->id]);
    }
}
