<?php

namespace App\Http\Requests\Flight;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SearchFlightRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize provider-neutral search input.
     */
    protected function prepareForValidation(): void
    {
        $tripType = $this->input('trip_type');
        $origin = $this->input('origin');
        $destination = $this->input('destination');
        $cabinClass = $this->input('cabin_class');

        $this->merge([
            'trip_type' => is_string($tripType)
                ? strtolower(trim($tripType))
                : $tripType,

            'origin' => is_string($origin)
                ? strtoupper(trim($origin))
                : $origin,

            'destination' => is_string($destination)
                ? strtoupper(trim($destination))
                : $destination,

            'cabin_class' => is_string($cabinClass)
                ? strtolower(trim($cabinClass))
                : $cabinClass,

            'children' => $this->input('children', 0),
            'infants' => $this->input('infants', 0),
        ]);
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'trip_type' => [
                'required',
                'string',
                Rule::in([
                    'one_way',
                    'round_trip',
                ]),
            ],

            'origin' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],

            'destination' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                'different:origin',
            ],

            'departure_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],

            'return_date' => [
                'nullable',
                'required_if:trip_type,round_trip',
                'prohibited_if:trip_type,one_way',
                'date_format:Y-m-d',
                'after:departure_date',
            ],

            'adults' => [
                'required',
                'integer',
                'min:1',
                'max:9',
            ],

            'children' => [
                'required',
                'integer',
                'min:0',
                'max:8',
            ],

            'infants' => [
                'required',
                'integer',
                'min:0',
                'max:8',
                'lte:adults',
            ],

            'cabin_class' => [
                'required',
                'string',
                Rule::in([
                    'economy',
                    'premium_economy',
                    'business',
                    'first',
                ]),
            ],
        ];
    }

    /**
     * Configure additional passenger validation.
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(function (
            Validator $validator
        ): void {
            if (
                $validator->errors()->has('adults') ||
                $validator->errors()->has('children') ||
                $validator->errors()->has('infants')
            ) {
                return;
            }

            $totalPassengers =
                (int) $this->input('adults') +
                (int) $this->input('children') +
                (int) $this->input('infants');

            if ($totalPassengers > 9) {
                $validator->errors()->add(
                    'passengers',
                    'The total number of passengers may not exceed 9.'
                );
            }
        });
    }
}
