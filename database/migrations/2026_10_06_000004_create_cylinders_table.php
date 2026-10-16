<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cylinders', function (Blueprint $table) {
            $table->id();
            $table->string('cylinder_code')->unique();
            $table->string('serial_number')->nullable()->unique();
            $table->foreignId('gas_type_id')
                ->constrained('gas_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->decimal('capacity', 8, 2)->nullable();
            $table->string('unit')->default('m3');
            $table->enum('status', [
                'AVAILABLE',
                'IN_USE',
                'EMPTY',
                'MAINTENANCE',
                'OUTSIDE',
                'INACTIVE',
            ])->default('AVAILABLE')->index();
            $table->enum('condition', [
                'GOOD',
                'DAMAGED',
                'MAINTENANCE',
            ])->default('GOOD')->index();
            $table->foreignId('current_location_id')
                ->nullable()
                ->constrained('locations')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->string('qr_token')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['gas_type_id', 'status']);
            $table->index(['current_location_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cylinders');
    }
};
