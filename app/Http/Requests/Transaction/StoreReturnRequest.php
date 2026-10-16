<?php

namespace App\Http\Requests\Transaction;

use App\Enums\CylinderCondition;
use App\Models\Cylinder;
use App\Rules\RemainingGasWithinCapacity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role->value, ['admin', 'operator'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cylinder_id' => ['required', 'exists:cylinders,id'],
            'to_location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'condition' => ['required', Rule::enum(CylinderCondition::class)],
            'remaining_gas' => ['nullable', 'numeric', 'min:0', new RemainingGasWithinCapacity($this->integer('cylinder_id'))],
            'transaction_date' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'cylinder_id.required' => 'Pilih tabung terlebih dahulu.',
            'cylinder_id.exists' => 'Tabung yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * Kembali ke form dengan tabung tetap terpilih agar error terlihat.
     */
    protected function failedValidation(Validator $validator): void
    {
        $code = Cylinder::whereKey($this->integer('cylinder_id'))->value('cylinder_code')
            ?? $this->query('cylinder');

        $redirect = $code
            ? redirect()->route('transactions.return.create', ['cylinder' => $code])
            : redirect()->back();

        throw new HttpResponseException(
            $redirect
                ->withErrors($validator)
                ->withInput()
        );
    }
}
