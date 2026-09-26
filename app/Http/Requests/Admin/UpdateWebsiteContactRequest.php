<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteContactRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s]{6,40}$/'],
            'whatsapp' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s]{6,40}$/'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:120'],
            'map_url' => ['nullable', 'url:https', 'max:500'],
            'facebook_url' => ['nullable', 'url:https', 'max:255'],
            'instagram_url' => ['nullable', 'url:https', 'max:255'],
            'linkedin_url' => ['nullable', 'url:https', 'max:255'],
            'youtube_url' => ['nullable', 'url:https', 'max:255'],
        ];
    }
}
