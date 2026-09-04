<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCurrencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currency = $this->route('currency');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('currencies', 'name')->ignore($currency),
            ],
            'code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::unique('currencies', 'code')->ignore($currency),
            ],
            'symbol' => [
                'nullable',
                'string',
                'max:16',
            ],
            'decimal_places' => [
                'required',
                'integer',
                'min:0',
                'max:4',
            ],
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:4294967295',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
