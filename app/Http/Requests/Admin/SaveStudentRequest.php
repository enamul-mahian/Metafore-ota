<?php

namespace App\Http\Requests\Admin;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('students.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('students', 'email')->ignore($student)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\s().-]{7,32}$/'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'reference_code' => [
                'required', 'string', 'max:64', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('students', 'reference_code')->ignore($student),
            ],
            'status' => ['required', 'string', Rule::in(Student::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->text('first_name'),
            'last_name' => $this->text('last_name'),
            'email' => strtolower($this->text('email') ?? ''),
            'phone' => $this->text('phone'),
            'date_of_birth' => $this->text('date_of_birth'),
            'reference_code' => strtoupper($this->text('reference_code') ?? ''),
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
