<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCityRequest extends FormRequest
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
        $city = $this->route('city');

        return [
            'country_id' => [
                'required',
                'integer',
                Rule::exists('countries', 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('cities', 'name')
                    ->where(
                        fn ($query) => $query->where(
                            'country_id',
                            $this->input('country_id')
                        )
                    )
                    ->ignore($city),
            ],
            'code' => [
                'nullable',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::unique('cities', 'code')->ignore($city),
            ],
            'timezone' => [
                'nullable',
                'string',
                'max:64',
                'timezone',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}