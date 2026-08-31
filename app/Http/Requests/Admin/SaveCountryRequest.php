<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCountryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $country = $this->route('country');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'iso2' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
                Rule::unique('countries', 'iso2')->ignore($country),
            ],
            'iso3' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::unique('countries', 'iso3')->ignore($country),
            ],
            'phone_code' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^\+?[0-9]+$/',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}