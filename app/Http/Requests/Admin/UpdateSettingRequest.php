<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => [
                'nullable',
            ],

            'type' => [
                'required',
                'string',
                Rule::in([
                    'string',
                    'integer',
                    'float',
                    'boolean',
                    'json',
                ]),
            ],

            'is_public' => [
                'required',
                'boolean',
            ],
        ];
    }
}