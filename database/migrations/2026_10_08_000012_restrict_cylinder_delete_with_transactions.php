<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat transaksi tidak boleh ikut terhapus saat tabung dihapus.
     * Controller sudah menolak hapus tabung bertransaksi, FK diperketat
     * ke RESTRICT sebagai pengaman level database.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: ubah FK butuh rebuild tabel; aplikasi menargetkan
            // MySQL untuk production, lewati dengan aman untuk testing.
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['cylinder_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('cylinder_id')
                ->references('id')
                ->on('cylinders')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['cylinder_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('cylinder_id')
                ->references('id')
                ->on('cylinders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
