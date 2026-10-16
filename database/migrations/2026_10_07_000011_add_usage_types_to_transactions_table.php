<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah tipe transaksi pemakaian produksi: USE_START & USE_END.
     * MySQL: enum perlu di-MODIFY. SQLite (testing): enum tidak dibatasi, lewati.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE transactions MODIFY transaction_type ENUM('IN','OUT','RETURN','TRANSFER','MAINTENANCE','USE_START','USE_END') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE transactions MODIFY transaction_type ENUM('IN','OUT','RETURN','TRANSFER','MAINTENANCE') NOT NULL");
    }
};
