<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteCustomCodeRequest extends FormRequest
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
        $max = (int) config('site.custom_code_max_length', 20000);

        return [
            'head' => ['nullable', 'string', 'max:'.$max],
            'body' => ['nullable', 'string', 'max:'.$max],
        ];
    }
}
