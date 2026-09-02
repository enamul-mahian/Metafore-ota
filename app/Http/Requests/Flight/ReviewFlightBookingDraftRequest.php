<?php

namespace App\Http\Requests\Flight;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewFlightBookingDraftRequest extends FormRequest
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
            'booking_draft_token' => [
                'required',
                'string',
                'size:64',
                'regex:/^[A-Za-z0-9]{64}$/',
            ],
        ];
    }
}
