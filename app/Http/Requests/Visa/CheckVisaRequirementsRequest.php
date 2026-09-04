<?php

namespace App\Http\Requests\Visa;

use Illuminate\Foundation\Http\FormRequest;

class CheckVisaRequirementsRequest extends FormRequest
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
            'nationality' => ['required', 'string', 'max:120'],
            'destination_country' => [
                'required',
                'string',
                'max:120',
                'different:nationality',
            ],
            'visa_type' => ['required', 'string', 'max:80'],
        ];
    }
}
