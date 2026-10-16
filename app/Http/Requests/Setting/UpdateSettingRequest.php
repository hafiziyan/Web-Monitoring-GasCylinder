<?php

namespace App\Http\Requests\Setting;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'maintenance_types' => ['required', 'string', 'max:2000'],
            'per_page' => ['required', 'integer', 'in:10,15,20,25,50'],
        ];
    }

    public function messages(): array
    {
        return [
            'maintenance_types.required' => 'Daftar jenis maintenance wajib diisi (minimal satu baris).',
        ];
    }
}
