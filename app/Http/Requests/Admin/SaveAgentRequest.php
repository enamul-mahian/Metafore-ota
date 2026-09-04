<?php

namespace App\Http\Requests\Admin;

use App\Models\Agent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agents.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $agent = $this->route('agent');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('agents', 'email')->ignore($agent),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^\+?[0-9\s().-]{7,32}$/',
            ],
            'company_name' => ['nullable', 'string', 'max:150'],
            'registration_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('agents', 'registration_number')->ignore($agent),
            ],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'status' => ['required', 'string', Rule::in(Agent::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizedString('name'),
            'email' => strtolower($this->normalizedString('email') ?? ''),
            'phone' => $this->normalizedString('phone'),
            'company_name' => $this->normalizedString('company_name'),
            'registration_number' => $this->normalizedString('registration_number'),
            'notes' => $this->normalizedString('notes'),
        ]);
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
