<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * qr_token dibuat nullable agar cylinder lama/import yang belum punya token
     * dapat dibuatkan token lewat halaman QR (tanpa mengubah token yang sudah ada).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `cylinders` MODIFY `qr_token` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `cylinders` MODIFY `qr_token` VARCHAR(255) NOT NULL');
    }
};
