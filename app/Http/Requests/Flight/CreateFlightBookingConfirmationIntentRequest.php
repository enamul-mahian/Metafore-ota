<?php

namespace App\Http\Requests\Flight;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFlightBookingConfirmationIntentRequest extends FormRequest
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
            ],

            'accept_revalidated_fare' => [
                'required',
                'accepted',
            ],

            'acknowledged_total_amount' => [
                'required',
                'string',
                'regex:/^\d{1,10}\.\d{2}$/',
            ],

            'acknowledged_currency' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],
        ];
    }
}
