<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorkVisaApplicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkVisaApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('work-visa.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(WorkVisaApplicationStatus::class)],
            'note' => ['nullable', 'string', 'max:2000'],
            'notify_applicant' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('note');

        $this->merge([
            'note' => is_string($note) && trim($note) !== '' ? trim($note) : null,
        ]);
    }
}
