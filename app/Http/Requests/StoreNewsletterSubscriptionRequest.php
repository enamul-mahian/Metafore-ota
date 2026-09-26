<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsletterSubscriptionRequest extends FormRequest
{
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9._-]+$/i'],
            StoreEnquiryRequest::HONEYPOT => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Keep newsletter errors apart from other forms on the same page.
     */
    protected $errorBag = 'newsletter';

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }
}
