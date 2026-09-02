<?php

namespace App\Http\Requests\Flight;

use Illuminate\Foundation\Http\FormRequest;

final class SelectFlightOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'selection_token' => [
                'required',
                'string',
                'size:64',
            ],
        ];
    }
}
