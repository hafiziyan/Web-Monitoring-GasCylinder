<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cylinder_id')
                ->constrained('cylinders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            // Mencakup semua nilai TransactionType sejak awal agar skema
            // fresh-install (termasuk SQLite untuk testing) sinkron dengan
            // enum aplikasi. Migrasi 000011 tetap ada untuk database yang
            // sudah terlanjur dibuat dengan 5 nilai awal.
            $table->enum('transaction_type', [
                'IN',
                'OUT',
                'RETURN',
                'TRANSFER',
                'MAINTENANCE',
                'USE_START',
                'USE_END',
            ])->index();
            $table->foreignId('from_location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('to_location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->string('condition')->nullable();
            $table->decimal('remaining_gas', 8, 2)->nullable();
            $table->string('destination')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('transaction_date')->index();
            $table->timestamps();

            $table->index(['cylinder_id', 'transaction_date']);
            $table->index(['user_id', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
