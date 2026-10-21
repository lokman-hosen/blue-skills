<?php

namespace App\Http\Requests\Organization;

use App\Http\Requests\Worker\StoreIdentityDocumentRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadCaseDocumentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:'.StoreIdentityDocumentRequest::MAX_KB],
            'employer_can_view' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.mimetypes' => __('worker.credential.type_error'),
            'document.max' => __('worker.credential.size_error', ['max' => StoreIdentityDocumentRequest::MAX_KB / 1024]),
        ];
    }
}
