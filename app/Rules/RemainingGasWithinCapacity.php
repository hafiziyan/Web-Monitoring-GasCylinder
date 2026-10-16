<?php

namespace App\Rules;

use App\Models\Cylinder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Sisa gas tidak boleh melebihi kapasitas tabung.
 * Dilewati jika tabung tidak ditemukan atau kapasitas kosong
 * (sudah ditangani rule exists/required lain).
 */
class RemainingGasWithinCapacity implements ValidationRule
{
    public function __construct(private readonly ?int $cylinderId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $capacity = $this->cylinderId
            ? Cylinder::whereKey($this->cylinderId)->value('capacity')
            : null;

        if ($capacity === null) {
            return;
        }

        if ((float) $value > (float) $capacity) {
            $fail("Sisa gas tidak boleh melebihi kapasitas tabung ({$capacity} m³).");
        }
    }
}
