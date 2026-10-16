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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private array $masters = [];

    private function masters(): array
    {
        if ($this->masters === []) {
            $gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
            $supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);
            $gudang = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
            $this->masters = [$gas, $supplier, $gudang];
        }

        return $this->masters;
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        [$gas, $supplier, $gudang] = $this->masters();

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-DASH-'.$counter,
            'gas_type_id' => $gas->id,
            'supplier_id' => $supplier->id,
            'capacity' => 6.00,
            'status' => CylinderStatus::Available,
            'condition' => CylinderCondition::Good,
            'current_location_id' => $gudang->id,
            'qr_token' => 'QR-DASH-'.$counter,
        ], $overrides));
    }

    public function test_guest_tidak_dapat_membuka_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_dashboard_dengan_menu_lengkap(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Gas Cylinder Monitoring System')
            ->assertSee('Users')
            ->assertSee('Settings')
            ->assertSee('Cetak QR');
    }

    public function test_operator_dapat_membuka_dashboard_tanpa_menu_admin(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Data Tabung')
            ->assertSee('Riwayat Transaksi')
            ->assertDontSee('Users')
            ->assertDontSee('Settings')
            ->assertDontSee('Cetak QR');
    }

    public function test_dashboard_menampilkan_statistik_berdasarkan_database(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);
        $location = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);

        foreach (['GAS-00001', 'GAS-00002'] as $i => $code) {
            Cylinder::create([
                'cylinder_code' => $code,
                'serial_number' => 'SN-'.$code,
                'gas_type_id' => $gas->id,
                'supplier_id' => $supplier->id,
                'capacity' => 6.00,
                'status' => CylinderStatus::Available,
                'condition' => CylinderCondition::Good,
                'current_location_id' => $location->id,
                'qr_token' => 'QR-'.$i.'-TEST',
            ]);
        }
        Cylinder::create([
            'cylinder_code' => 'GAS-00003',
            'serial_number' => 'SN-GAS-00003',
            'gas_type_id' => $gas->id,
            'supplier_id' => $supplier->id,
            'capacity' => 6.00,
            'status' => CylinderStatus::InUse,
            'condition' => CylinderCondition::Good,
            'current_location_id' => $location->id,
            'qr_token' => 'QR-3-TEST',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('<p class="mt-1 text-2xl font-bold text-gray-900" data-test="stat-total">3</p>', false)
            ->assertSee('data-test="stat-available">2<', false)
            ->assertSee('data-test="stat-in_use">1<', false)
            ->assertSee('data-test="stat-empty">0<', false)
            ->assertSee('Oxygen')
            ->assertSee('Gudang Gas');
    }

    public function test_dashboard_menampilkan_aktivitas_transaksi_terbaru(): void
    {
        $user = User::factory()->create(['name' => 'Operator Satu', 'role' => UserRole::Operator]);
        $gas = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);
        $gudang = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
        $welding = Location::create(['name' => 'Welding Area', 'code' => 'LOC-002', 'is_active' => true]);
        $cylinder = Cylinder::create([
            'cylinder_code' => 'GAS-00001',
            'serial_number' => 'SN-GAS-00001',
            'gas_type_id' => $gas->id,
            'supplier_id' => $supplier->id,
            'capacity' => 6.00,
            'status' => CylinderStatus::InUse,
            'condition' => CylinderCondition::Good,
            'current_location_id' => $welding->id,
            'qr_token' => 'QR-1-TEST',
        ]);
        Transaction::create([
            'cylinder_id' => $cylinder->id,
            'user_id' => $user->id,
            'transaction_type' => TransactionType::Out,
            'from_location_id' => $gudang->id,
            'to_location_id' => $welding->id,
            'condition' => 'GOOD',
            'remaining_gas' => 6.00,
            'transaction_date' => '2026-09-15 10:00:00',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Aktivitas Terbaru')
            ->assertSee('GAS-00001')
            ->assertSee('TABUNG KELUAR')
            ->assertSee('Operator Satu')
            ->assertSee('Gudang Gas')
            ->assertSee('Welding Area');
    }

    public function test_statistik_status_lengkap_benar(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $this->makeCylinder(['status' => CylinderStatus::Available]);
        $this->makeCylinder(['status' => CylinderStatus::InUse]);
        $this->makeCylinder(['status' => CylinderStatus::Empty]);
        $this->makeCylinder(['status' => CylinderStatus::Maintenance, 'condition' => CylinderCondition::Maintenance]);
        $this->makeCylinder(['status' => CylinderStatus::Outside, 'current_location_id' => null]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-test="stat-total">5<', false)
            ->assertSee('data-test="stat-available">1<', false)
            ->assertSee('data-test="stat-in_use">1<', false)
            ->assertSee('data-test="stat-empty">1<', false)
            ->assertSee('data-test="stat-maintenance">1<', false)
            ->assertSee('data-test="stat-outside">1<', false);
    }

    public function test_distribusi_gas_dan_lokasi_benar(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        [$gas, , $gudang] = $this->masters();
        $argon = GasType::create(['name' => 'Argon', 'code' => 'AR', 'is_active' => true]);
        $this->makeCylinder(['gas_type_id' => $gas->id]);
        $this->makeCylinder(['gas_type_id' => $gas->id]);
        $this->makeCylinder(['gas_type_id' => $argon->id]);
        $this->makeCylinder(['status' => CylinderStatus::Outside, 'current_location_id' => null]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Oxygen');
        $response->assertSee('Argon');
        $response->assertSee('Gudang Gas');
        // OUTSIDE dihitung terpisah, bukan sebagai lokasi.
        $response->assertSee('Tabung di Luar Perusahaan');
        $response->assertSee('data-test="stat-outside-location">1<', false);
    }

    public function test_aktivitas_diurutkan_terbaru_dulu(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        foreach (['2026-09-01 08:00:00', '2026-09-10 08:00:00'] as $i => $date) {
            Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $user->id,
                'transaction_type' => $i === 0 ? TransactionType::In : TransactionType::Out,
                'to_location_id' => $this->masters()[2]->id,
                'transaction_date' => $date,
            ]);
        }

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['10 Sep 2026', '01 Sep 2026'])
            ->assertSeeInOrder(['TABUNG KELUAR', 'TABUNG MASUK']);
    }

    public function test_ringkasan_kondisi_benar(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $this->makeCylinder(['condition' => CylinderCondition::Good]);
        $this->makeCylinder(['condition' => CylinderCondition::Good]);
        $this->makeCylinder(['condition' => CylinderCondition::Damaged]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-test="stat-condition-good">2<', false)
            ->assertSee('data-test="stat-condition-damaged">1<', false)
            ->assertSee('data-test="stat-condition-maintenance">0<', false);
    }

    public function test_quick_actions_tersedia_dengan_route_benar(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Quick Actions')
            ->assertSee(route('transactions.scan'), false)
            ->assertSee(route('transactions.in.create'), false)
            ->assertSee(route('transactions.out.create'), false)
            ->assertSee(route('transactions.return.create'), false)
            ->assertSee(route('transactions.maintenance.create'), false);
    }

    public function test_aksi_tersering_dan_link_aktivitas(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $in = Transaction::create([
            'cylinder_id' => $cylinder->id, 'user_id' => $user->id,
            'transaction_type' => TransactionType::In,
            'to_location_id' => $this->masters()[2]->id,
            'transaction_date' => '2026-09-01 08:00:00',
        ]);
        Transaction::create([
            'cylinder_id' => $cylinder->id, 'user_id' => $user->id,
            'transaction_type' => TransactionType::In,
            'to_location_id' => $this->masters()[2]->id,
            'transaction_date' => '2026-09-02 08:00:00',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Aksi Transaksi Tersering')
            ->assertSee('2x')
            ->assertSee(route('transactions.show', $in), false);
    }

    public function test_database_kosong_tidak_error(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-test="stat-total">0<', false)
            ->assertSee('Belum ada aktivitas transaksi.');
    }

    public function test_dashboard_tanpa_n_plus_satu(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $cylinder = $this->makeCylinder();
        for ($i = 1; $i <= 8; $i++) {
            Transaction::create([
                'cylinder_id' => $cylinder->id, 'user_id' => $user->id,
                'transaction_type' => TransactionType::In,
                'to_location_id' => $this->masters()[2]->id,
                'transaction_date' => '2026-09-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).' 08:00:00',
            ]);
        }

        DB::enableQueryLog();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $queryCount = count(DB::getQueryLog());

        $this->assertLessThanOrEqual(20, $queryCount, "Terlalu banyak query: {$queryCount}");
    }
}
