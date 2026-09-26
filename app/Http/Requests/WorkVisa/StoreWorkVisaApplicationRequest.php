<?php

namespace App\Http\Requests\WorkVisa;

use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDestination;
use App\Models\WorkVisaDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkVisaApplicationRequest extends FormRequest
{
    public const OTHER_JOB_CATEGORY = 'Other';

    public function authorize(): bool
    {
        return $this->user()?->can('work-visa.apply') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $file = ['file', 'mimes:'.implode(',', WorkVisaDocument::MIMES), 'max:'.WorkVisaDocument::MAX_KILOBYTES];

        return [
            'work_visa_destination_id' => [
                'required',
                'integer',
                Rule::exists('work_visa_destinations', 'id')->where('is_active', true),
            ],
            'job_category' => ['required', 'string', 'max:150', $this->jobCategoryRule()],
            'title' => ['nullable', 'string', Rule::in(WorkVisaApplication::TITLES)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString(), 'after:'.now()->subYears(80)->toDateString()],
            'gender' => ['required', 'string', Rule::in(WorkVisaApplication::GENDERS)],
            'nationality' => ['required', 'string', 'max:100'],
            'passport_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]{6,20}$/'],
            'passport_expiry' => ['required', 'date', 'after:today'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9\s().-]{7,32}$/'],
            'email' => ['required', 'email', 'max:255'],
            'current_address' => ['required', 'string', 'max:500'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'passport' => ['required', ...$file],
            'photo' => ['nullable', ...$file],
            'cv' => ['nullable', ...$file],
            'certificates' => ['nullable', 'array', 'max:5'],
            'certificates.*' => [...$file],
            'confirm' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'work_visa_destination_id' => 'destination',
            'passport' => 'passport bio page',
            'photo' => 'photo',
            'cv' => 'CV',
            'certificates.*' => 'certificate',
            'confirm' => 'confirmation',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'Applicants must be at least 18 years old.',
            'passport_number.regex' => 'Enter the passport number as printed: letters and numbers only.',
            'passport_expiry.after' => 'Your passport must still be valid.',
            'passport.required' => 'Please upload the bio page of your passport.',
            'confirm.accepted' => 'Please confirm that the information is correct.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $passport = $this->input('passport_number');

        $this->merge([
            'first_name' => $this->normalizedString('first_name'),
            'last_name' => $this->normalizedString('last_name'),
            'nationality' => $this->normalizedString('nationality'),
            'passport_number' => is_string($passport) ? strtoupper((string) preg_replace('/\s+/', '', $passport)) : $passport,
            'phone' => $this->normalizedString('phone'),
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : $this->input('email'),
            'current_address' => $this->normalizedString('current_address'),
            'job_category' => $this->normalizedString('job_category'),
            'notes' => $this->normalizedString('notes'),
            'title' => $this->normalizedString('title'),
            'experience_years' => $this->normalizedString('experience_years'),
        ]);
    }

    /**
     * When the destination lists job categories, the choice must be one of them (or "Other").
     */
    private function jobCategoryRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $destination = WorkVisaDestination::query()->find($this->integer('work_visa_destination_id'));

            if ($destination === null || $value === self::OTHER_JOB_CATEGORY) {
                return;
            }

            $categories = $destination->jobCategoryList();

            if ($categories !== [] && ! in_array($value, $categories, true)) {
                $fail('Choose a job category offered for this destination.');
            }
        };
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
