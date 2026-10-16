<?php

namespace App\Http\Requests\Cylinder;

use App\Enums\CylinderCondition;
use App\Enums\CylinderStatus;
use App\Models\Cylinder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCylinderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Cylinder::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cylinder_code' => ['required', 'string', 'max:50', 'unique:cylinders,cylinder_code'],
            'serial_number' => ['nullable', 'string', 'max:50', 'unique:cylinders,serial_number'],
            // Data baru hanya boleh memakai master yang aktif.
            'gas_type_id' => ['required', Rule::exists('gas_types', 'id')->where('is_active', true)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            // Tabung baru lahir dalam keadaan internal (siap didata).
            // Status lain (IN_USE/OUTSIDE/MAINTENANCE/INACTIVE) hanya boleh
            // dicapai melalui transaksi agar riwayat selalu lengkap.
            'status' => ['required', Rule::in([CylinderStatus::Available->value, CylinderStatus::Empty->value])],
            'condition' => ['required', Rule::enum(CylinderCondition::class)],
            'current_location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            // qr_token selalu dibuatkan sistem (tidak bisa diinput user
            // agar tidak bisa ditebak/di-squat).
            'notes' => ['nullable', 'string'],
        ];
    }
}
