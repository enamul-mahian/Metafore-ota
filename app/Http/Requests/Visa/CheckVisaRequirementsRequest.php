<?php

namespace App\Http\Requests\Visa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckVisaRequirementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $activeIso3 = Rule::exists(
            'countries',
            'iso3'
        )->where(
            static fn ($query) => $query->where(
                'is_active',
                true
            )
        );

        return [
            'nationality' => [
                'required',
                'string',
                'size:3',
                $activeIso3,
            ],
            'origin_country' => [
                'required',
                'string',
                'size:3',
                $activeIso3,
            ],
            'destination_country' => [
                'required',
                'string',
                'size:3',
                'different:origin_country',
                $activeIso3,
            ],
            'departure_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'departure_time' => [
                'required',
                'date_format:H:i',
            ],
            'arrival_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:departure_date',
            ],
            'arrival_time' => [
                'required',
                'date_format:H:i',
            ],
        ];
    }
}
