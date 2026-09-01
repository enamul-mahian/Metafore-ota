<?php

namespace App\Http\Requests\Flight;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ValidateFlightTravelersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selection_token' => [
                'required',
                'string',
                'size:64',
            ],

            'travelers' => [
                'required',
                'array',
                'min:1',
                'max:9',
            ],

            'travelers.*.type' => [
                'required',
                'string',
                Rule::in([
                    'adult',
                    'child',
                    'infant',
                ]),
            ],

            'travelers.*.title' => [
                'required',
                'string',
                Rule::in([
                    'mr',
                    'ms',
                    'mrs',
                    'mstr',
                    'miss',
                ]),
            ],

            'travelers.*.given_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[\pL\pM][\pL\pM\s.\'-]{0,49}$/u',
            ],

            'travelers.*.family_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[\pL\pM][\pL\pM\s.\'-]{0,49}$/u',
            ],

            'travelers.*.gender' => [
                'sometimes',
                'required',
                'string',
                'in:m,f',
            ],

            'travelers.*.email' => [
                'sometimes',
                'required',
                'string',
                'email:rfc',
                'max:254',
            ],

            'travelers.*.phone_number' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\+[1-9][0-9]{6,14}$/',
            ],

            'travelers.*.date_of_birth' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }
}
