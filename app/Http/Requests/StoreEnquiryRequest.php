<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    public const HONEYPOT = 'website';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...self::contactRules(messageRequired: false),
            ...self::detailRules($this->fieldDefinitions()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect($this->fieldDefinitions())
            ->map(fn (array $field): string => mb_strtolower($field['label']))
            ->all();
    }

    /**
     * The service-specific values, keyed by field, with empty values removed.
     *
     * @return array<string, string|int>
     */
    public function details(): array
    {
        $validated = $this->validated();
        $details = [];

        foreach ($this->fieldDefinitions() as $key => $field) {
            $value = $validated[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $details[$key] = $field['type'] === 'number' ? (int) $value : (string) $value;
        }

        return $details;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function fieldDefinitions(): array
    {
        return config('enquiries.services.'.$this->route('service').'.fields', []);
    }

    /**
     * Name, email, phone, message, source and the spam honeypot.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function contactRules(bool $messageRequired): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s.]{6,32}$/'],
            'message' => [$messageRequired ? 'required' : 'nullable', 'string', 'max:3000'],
            'source_url' => ['nullable', 'string', 'max:2048'],
            self::HONEYPOT => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Build validation rules from the config/enquiries.php field definitions.
     *
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<string, array<int, mixed>>
     */
    public static function detailRules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $key => $field) {
            $fieldRules = [($field['required'] ?? false) ? 'required' : 'nullable'];

            switch ($field['type']) {
                case 'number':
                    array_push($fieldRules, 'integer', 'min:'.($field['min'] ?? 0), 'max:'.($field['max'] ?? 999));
                    break;
                case 'date':
                    $fieldRules[] = 'date_format:Y-m-d';
                    if ($field['future'] ?? false) {
                        $fieldRules[] = 'after_or_equal:today';
                    }
                    if (isset($field['after'])) {
                        $fieldRules[] = (($field['strict'] ?? false) ? 'after:' : 'after_or_equal:').$field['after'];
                    }
                    break;
                case 'month':
                    $fieldRules[] = 'date_format:Y-m';
                    if ($field['future'] ?? false) {
                        $fieldRules[] = function (string $attribute, mixed $value, Closure $fail): void {
                            if (is_string($value) && $value < now()->format('Y-m')) {
                                $fail('The :attribute must be this month or later.');
                            }
                        };
                    }
                    break;
                case 'time':
                    $fieldRules[] = 'date_format:H:i';
                    break;
                case 'select':
                    $fieldRules[] = Rule::in($field['options'] ?? []);
                    break;
                default:
                    array_push($fieldRules, 'string', 'max:'.($field['max'] ?? 255));
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Keep a submitted page address only when it points at this website.
     */
    public static function sameSiteUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && $host === request()->getHost() ? mb_substr($url, 0, 2048) : null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }
}
