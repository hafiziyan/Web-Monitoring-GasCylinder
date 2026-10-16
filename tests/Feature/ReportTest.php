<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private GasType $oxygen;

    private GasType $argon;

    private Location $gudang;

    private Location $welding;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Admin Laporan', 'role' => UserRole::Admin]);
        $this->operator = User::factory()->create(['name' => 'Operator Laporan', 'role' => UserRole::Operator]);
        $this->oxygen = GasType::create(['name' => 'Oxygen', 'code' => 'O2', 'is_active' => true]);
        $this->argon = GasType::create(['name' => 'Argon', 'code' => 'AR', 'is_active' => true]);
        $this->gudang = Location::create(['name' => 'Gudang Gas', 'code' => 'LOC-001', 'is_active' => true]);
        $this->welding = Location::create(['name' => 'Welding Area', 'code' => 'LOC-002', 'is_active' => true]);
        $this->supplier = Supplier::create(['name' => 'PT Gas', 'code' => 'SUP-001', 'is_active' => true]);
    }

    private function makeCylinder(array $overrides = []): Cylinder
    {
        static $counter = 0;
        $counter++;

        return Cylinder::create(array_merge([
            'cylinder_code' => 'GAS-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-REP-'.$counter,
            'gas_type_id' => $this->oxygen->id,
            'supplier_id' => $this->supplier->id,
            'capacity' => 6.00,
            'unit' => 'm3',
            'status' => 'AVAILABLE',
            'condition' => 'GOOD',
            'current_location_id' => $this->gudang->id,
            'qr_token' => 'CYL-REP'.$counter,
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

    public function test_guest_tidak_dapat_membuka_laporan_transaksi(): void
    {
        $this->get('/reports/transactions')->assertRedirect('/login');
    }

    public function test_admin_dapat_membuka_laporan(): void
    {
        $this->actingAs($this->admin)->get('/reports/transactions')->assertOk()->assertSee('Laporan Transaksi');
    }

    public function test_operator_dapat_membuka_laporan(): void
    {
        $this->actingAs($this->operator)->get('/reports/transactions')->assertOk()->assertSee('Laporan Transaksi');
    }

    public function test_transaksi_tampil_dan_search(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'serial_number' => 'SN-UNIK', 'qr_token' => 'CYL-TOKUNIK']);
        $this->makeTransaction($cylinder);
        $other = $this->makeCylinder(['cylinder_code' => 'GAS-00002', 'gas_type_id' => $this->argon->id]);
        $this->makeTransaction($other);

        $this->actingAs($this->admin)->get('/reports/transactions')->assertOk()->assertSee('GAS-00001');

        foreach (['GAS-00001', 'SN-UNIK', 'CYL-TOKUNIK', 'Oxygen'] as $keyword) {
            $response = $this->actingAs($this->admin)->get('/reports/transactions?search='.urlencode($keyword));
            $response->assertOk();
            $response->assertSee('GAS-00001');
            $response->assertDontSee('GAS-00002');
        }

        $this->actingAs($this->admin)
            ->get('/reports/transactions?search='.urlencode('Operator Laporan'))
            ->assertOk()
            ->assertSee('GAS-00001');
    }

    public function test_filter_tipe_gas_lokasi_dan_tanggal(): void
    {
        $oxy = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $this->makeTransaction($oxy, ['transaction_type' => 'IN', 'to_location_id' => $this->gudang->id, 'from_location_id' => null, 'transaction_date' => '2026-10-01 08:00:00']);
        $arg = $this->makeCylinder(['cylinder_code' => 'GAS-00002', 'gas_type_id' => $this->argon->id]);
        $this->makeTransaction($arg, ['transaction_type' => 'OUT', 'transaction_date' => '2026-10-05 08:00:00']);

        $this->actingAs($this->admin)->get('/reports/transactions?type=OUT')->assertOk()->assertSee('GAS-00002')->assertDontSee('GAS-00001');
        $this->actingAs($this->admin)->get('/reports/transactions?gas_type_id='.$this->argon->id)->assertOk()->assertSee('GAS-00002')->assertDontSee('GAS-00001');
        $this->actingAs($this->admin)->get('/reports/transactions?from_location_id='.$this->gudang->id)->assertOk()->assertSee('GAS-00002')->assertDontSee('GAS-00001');
        $this->actingAs($this->admin)->get('/reports/transactions?to_location_id='.$this->gudang->id)->assertOk()->assertSee('GAS-00001')->assertDontSee('GAS-00002');
        $this->actingAs($this->admin)->get('/reports/transactions?date_from=2026-10-03')->assertOk()->assertSee('GAS-00002')->assertDontSee('GAS-00001');
        // Tanggal sampai mencakup 23:59:59.
        $late = $this->makeCylinder(['cylinder_code' => 'GAS-00003']);
        $this->makeTransaction($late, ['transaction_date' => '2026-10-06 23:59:59']);
        $this->actingAs($this->admin)->get('/reports/transactions?date_to=2026-10-06')->assertOk()->assertSee('GAS-00003');
    }

    public function test_pagination_dan_filter_terbawa(): void
    {
        $cylinder = $this->makeCylinder();
        for ($i = 1; $i <= 21; $i++) {
            $this->makeTransaction($cylinder, ['transaction_date' => '2026-10-05 10:'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).':00']);
        }

        // Default per_page aplikasi adalah 10: header + 10 baris.
        $page1 = $this->actingAs($this->admin)->get('/reports/transactions?type=OUT');
        $page1->assertOk();
        $this->assertSame(11, substr_count($page1->getContent(), '<tr'));

        $page2 = $this->actingAs($this->admin)->get('/reports/transactions?type=OUT&page=2');
        $page2->assertOk();
        $page2->assertSee('type=OUT', false);
    }

    public function test_summary_mengikuti_filter(): void
    {
        $cylinder = $this->makeCylinder();
        $this->makeTransaction($cylinder, ['transaction_type' => 'IN', 'to_location_id' => $this->gudang->id, 'from_location_id' => null]);
        $this->makeTransaction($cylinder, ['transaction_type' => 'OUT']);

        $this->actingAs($this->admin)
            ->get('/reports/transactions?type=OUT')
            ->assertOk()
            ->assertSee('data-test="summary-total">1<', false)
            ->assertSee('data-test="summary-out">1<', false)
            ->assertSee('data-test="summary-in">0<', false);
    }

    public function test_export_excel_mengikuti_filter(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $this->makeTransaction($cylinder, ['transaction_type' => 'IN', 'to_location_id' => $this->gudang->id, 'from_location_id' => null]);
        $other = $this->makeCylinder(['cylinder_code' => 'GAS-00002']);
        $this->makeTransaction($other);

        $response = $this->actingAs($this->admin)->get('/reports/transactions/excel?type=IN');
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'trx').'.xlsx';
        file_put_contents($path, $content);
        $rows = (new Xlsx)->load($path)->getActiveSheet()->toArray();
        unlink($path);

        $this->assertSame(['No', 'Tanggal', 'Kode Tabung', 'Serial Number', 'Jenis Gas', 'Tipe Transaksi', 'Lokasi Asal', 'Lokasi Tujuan', 'Tujuan', 'Kondisi', 'Sisa Gas', 'Operator', 'Catatan'], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame('GAS-00001', $rows[1][2]);
    }

    public function test_export_pdf_mengikuti_filter(): void
    {
        $cylinder = $this->makeCylinder(['cylinder_code' => 'GAS-00001']);
        $this->makeTransaction($cylinder);

        $response = $this->actingAs($this->admin)->get('/reports/transactions/pdf');
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_guest_tidak_dapat_export(): void
    {
        $this->get('/reports/transactions/excel')->assertRedirect('/login');
        $this->get('/reports/transactions/pdf')->assertRedirect('/login');
        $this->get('/reports/cylinders/excel')->assertRedirect('/login');
        $this->get('/reports/cylinders/pdf')->assertRedirect('/login');
    }

    public function test_laporan_tabung_dan_filter(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'serial_number' => 'SN-A', 'qr_token' => 'CYL-A', 'status' => 'AVAILABLE', 'condition' => 'GOOD']);
        $this->makeCylinder(['cylinder_code' => 'GAS-00002', 'serial_number' => 'SN-B', 'qr_token' => 'CYL-B', 'status' => 'EMPTY', 'condition' => 'DAMAGED', 'supplier_id' => null, 'current_location_id' => $this->welding->id]);

        $this->actingAs($this->admin)->get('/reports/cylinders')->assertOk()->assertSee('Laporan Tabung')->assertSee('GAS-00001');
        $this->actingAs($this->operator)->get('/reports/cylinders')->assertOk()->assertSee('GAS-00001');

        foreach (['search=GAS-00001', 'search=SN-A', 'search=CYL-A'] as $query) {
            $response = $this->actingAs($this->admin)->get('/reports/cylinders?'.$query);
            $response->assertOk();
            $response->assertSee('GAS-00001');
            $response->assertDontSee('GAS-00002');
        }

        $this->actingAs($this->admin)->get('/reports/cylinders?status=EMPTY')->assertOk()->assertSee('GAS-00002')->assertDontSee('GAS-00001');
        $this->actingAs($this->admin)->get('/reports/cylinders?gas_type_id='.$this->argon->id)->assertOk()->assertDontSee('GAS-00001');
        $this->actingAs($this->admin)->get('/reports/cylinders?supplier_id='.$this->supplier->id)->assertOk()->assertSee('GAS-00001')->assertDontSee('GAS-00002');
        $this->actingAs($this->admin)->get('/reports/cylinders?condition=DAMAGED')->assertOk()->assertSee('GAS-00002')->assertDontSee('GAS-00001');
        $this->actingAs($this->admin)->get('/reports/cylinders?current_location_id='.$this->welding->id)->assertOk()->assertSee('GAS-00002')->assertDontSee('GAS-00001');
    }

    public function test_export_excel_dan_pdf_tabung(): void
    {
        $this->makeCylinder(['cylinder_code' => 'GAS-00001', 'status' => 'AVAILABLE']);
        $this->makeCylinder(['cylinder_code' => 'GAS-00002', 'status' => 'EMPTY']);

        $excel = $this->actingAs($this->admin)->get('/reports/cylinders/excel?status=EMPTY');
        $excel->assertOk();
        $excel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        ob_start();
        $excel->sendContent();
        $content = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'cyl').'.xlsx';
        file_put_contents($path, $content);
        $rows = (new Xlsx)->load($path)->getActiveSheet()->toArray();
        unlink($path);

        $this->assertSame(['No', 'Kode Tabung', 'Serial Number', 'Jenis Gas', 'Kapasitas', 'Supplier', 'Status', 'Kondisi', 'Lokasi Saat Ini', 'QR Token', 'Update Terakhir'], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame('GAS-00002', $rows[1][1]);

        $pdf = $this->actingAs($this->admin)->get('/reports/cylinders/pdf?status=EMPTY');
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_database_kosong_tidak_error(): void
    {
        $this->actingAs($this->admin)->get('/reports/transactions')->assertOk()->assertSee('Data laporan tidak ditemukan.');
        $this->actingAs($this->admin)->get('/reports/cylinders')->assertOk();
        $this->actingAs($this->admin)->get('/reports/transactions/excel')->assertOk();
        $this->actingAs($this->admin)->get('/reports/cylinders/pdf')->assertOk();
    }
}
