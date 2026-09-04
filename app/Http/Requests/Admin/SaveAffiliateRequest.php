<?php

namespace App\Http\Requests\Admin;

use App\Models\Affiliate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAffiliateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('affiliates.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $affiliate = $this->route('affiliate');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('affiliates', 'email')->ignore($affiliate)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\s().-]{7,32}$/'],
            'organization_name' => ['nullable', 'string', 'max:150'],
            'referral_code' => [
                'required', 'string', 'max:64', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('affiliates', 'referral_code')->ignore($affiliate),
            ],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'status' => ['required', 'string', Rule::in(Affiliate::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->text('name'),
            'email' => strtolower($this->text('email') ?? ''),
            'phone' => $this->text('phone'),
            'organization_name' => $this->text('organization_name'),
            'referral_code' => strtoupper($this->text('referral_code') ?? ''),
            'website_url' => $this->text('website_url'),
            'notes' => $this->text('notes'),
        ]);
    }

    private function text(string $key): ?string
    {
        $value = $this->input($key);
        if (! is_string($value)) {
            return $value === null ? null : '';
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
