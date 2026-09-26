<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(
            collect(config('site.tracking_fields', []))
                ->keys()
                ->mapWithKeys(fn (string $field): array => [$field => trim((string) $this->input($field, ''))])
                ->all()
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return collect(config('site.tracking_fields', []))
            ->mapWithKeys(fn (array $definition, string $field): array => [
                $field => ['nullable', 'string', 'max:120', 'regex:'.$definition['pattern']],
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return collect(config('site.tracking_fields', []))
            ->mapWithKeys(fn (array $definition, string $field): array => [
                $field.'.regex' => $definition['label'].' should look like '.$definition['placeholder'].'.',
            ])
            ->all();
    }
}
