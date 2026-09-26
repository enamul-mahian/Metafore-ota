<?php

namespace App\Http\Requests\WorkVisa;

use App\Models\WorkVisaDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkVisaDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(WorkVisaDocument::TYPES))],
            'document' => ['required', 'file', 'mimes:'.implode(',', WorkVisaDocument::MIMES), 'max:'.WorkVisaDocument::MAX_KILOBYTES],
        ];
    }
}
