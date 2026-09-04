<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $language = $this->route('language');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('languages', 'name')->ignore($language),
            ],
            'code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[a-z]{2}$/',
                Rule::unique('languages', 'code')->ignore($language),
            ],
            'native_name' => [
                'nullable',
                'string',
                'max:150',
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
