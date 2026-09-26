<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveWorkVisaDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('work-visa.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $destination = $this->route('destination');

        return [
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('work_visa_destinations', 'slug')->ignore($destination),
            ],
            'visa_type' => ['nullable', 'string', 'max:150'],
            'summary' => ['nullable', 'string', 'max:500'],
            'processing_time' => ['nullable', 'string', 'max:100'],
            'service_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'job_categories' => ['array', 'max:40'],
            'job_categories.*' => ['string', 'max:150', 'distinct'],
            'requirements' => ['array', 'max:40'],
            'requirements.*' => ['string', 'max:200', 'distinct'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'country_id' => 'country',
            'job_categories.*' => 'job category',
            'requirements.*' => 'requirement',
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');
        $fee = $this->input('service_fee');
        $currency = $this->input('currency');

        $this->merge([
            'slug' => is_string($slug) && trim($slug) !== '' ? Str::slug($slug) : $slug,
            'visa_type' => $this->normalizedString('visa_type'),
            'summary' => $this->normalizedString('summary'),
            'processing_time' => $this->normalizedString('processing_time'),
            'service_fee' => is_string($fee) && trim($fee) === '' ? null : $fee,
            'currency' => is_string($currency) && trim($currency) !== '' ? strtoupper(trim($currency)) : 'BDT',
            'job_categories' => $this->lines('job_categories'),
            'requirements' => $this->lines('requirements'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
        ]);
    }

    /**
     * Accept either an array or a textarea with one entry per line.
     *
     * @return list<mixed>
     */
    private function lines(string $key): array
    {
        $value = $this->input($key);

        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $line): mixed => is_string($line) ? trim($line) : $line, $value),
            fn (mixed $line): bool => $line !== '' && $line !== null,
        ));
    }

    private function normalizedString(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return $value === null ? null : '';
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
