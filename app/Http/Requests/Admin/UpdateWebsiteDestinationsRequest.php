<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteDestinationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['present', 'array', 'max:24'],
            'items.*.name' => ['required', 'string', 'max:60'],
            'items.*.country' => ['nullable', 'string', 'max:60'],
            'items.*.image' => ['nullable', 'string', 'max:255'],
            'items.*.upload' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('site.image_upload_max_kb', 4096)],
            'items.*.active' => ['nullable', 'boolean'],
            'items.*.sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'items.*.remove' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'items.*.name' => 'destination name',
            'items.*.country' => 'country',
            'items.*.upload' => 'destination image',
        ];
    }
}
