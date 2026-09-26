<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteSeoRequest extends FormRequest
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
        $rules = ['pages' => ['present', 'array']];

        foreach (array_keys(config('site.seo_pages', [])) as $routeName) {
            $field = 'pages.'.str_replace('.', '\.', $routeName);
            $rules[$field.'.title'] = ['nullable', 'string', 'max:70'];
            $rules[$field.'.description'] = ['nullable', 'string', 'max:170'];
        }

        return $rules;
    }
}
