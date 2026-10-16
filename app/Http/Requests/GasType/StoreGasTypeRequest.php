<?php

namespace App\Http\Requests\GasType;

use App\Models\GasType;
use Illuminate\Foundation\Http\FormRequest;

class StoreGasTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', GasType::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:gas_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
