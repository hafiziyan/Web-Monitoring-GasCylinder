<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * current_location_id dibuat nullable: tabung OUTSIDE tidak berada di lokasi internal mana pun.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `cylinders` MODIFY `current_location_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `cylinders` MODIFY `current_location_id` BIGINT UNSIGNED NOT NULL');
    }
};
