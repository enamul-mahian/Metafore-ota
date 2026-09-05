<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('institutions.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $institution = $this->route('institution');

        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('institutions', 'email')->ignore($institution)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\s().-]{7,32}$/'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'registration_number' => [
                'nullable', 'string', 'max:100',
                Rule::unique('institutions', 'registration_number')->ignore($institution),
            ],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(Institution::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->text('name'),
            'email' => ($email = $this->text('email')) === null ? null : strtolower($email),
            'phone' => $this->text('phone'),
            'website_url' => $this->text('website_url'),
            'registration_number' => $this->text('registration_number'),
            'address' => $this->text('address'),
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
