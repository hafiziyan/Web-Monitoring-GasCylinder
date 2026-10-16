<?php

namespace App\Http\Requests\Cylinder;

use App\Enums\CylinderCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCylinderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cylinder'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cylinder = $this->route('cylinder');
        $cylinderId = $cylinder->id;

        // Nilai yang sedang dipakai tetap valid walau sudah nonaktif; pilihan lain harus aktif.
        $activeOrCurrent = fn (string $table, ?int $currentId) => Rule::exists($table, 'id')->where(
            fn ($q) => $q->where('is_active', true)->when($currentId, fn ($q) => $q->orWhere('id', $currentId))
        );

        return [
            // cylinder_code & qr_token identitas permanen: tidak diikutkan agar tidak bisa diubah.
            // status & current_location_id juga dikunci: hanya boleh berubah
            // melalui transaksi (state-machine di TransactionController).
            'serial_number' => ['nullable', 'string', 'max:50', Rule::unique('cylinders', 'serial_number')->ignore($cylinderId)],
            'gas_type_id' => ['required', $activeOrCurrent('gas_types', $cylinder->gas_type_id)],
            'supplier_id' => ['nullable', $activeOrCurrent('suppliers', $cylinder->supplier_id)],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'condition' => ['required', Rule::enum(CylinderCondition::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
