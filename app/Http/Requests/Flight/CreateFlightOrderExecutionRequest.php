<?php

namespace App\Http\Requests\Flight;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFlightOrderExecutionRequest extends FormRequest
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
            'confirmation_intent_token' => [
                'required',
                'string',
                'size:64',
            ],
        ];
    }
}