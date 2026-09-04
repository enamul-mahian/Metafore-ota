<?php

namespace App\Http\Requests\Tour;

use Illuminate\Foundation\Http\FormRequest;

class SearchToursRequest extends FormRequest
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
            'destination' => ['required', 'string', 'max:120'],
            'travel_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'travelers' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
