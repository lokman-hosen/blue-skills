<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShareCaseDocumentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // A new file, or a scan already on the Documents page.
        return [
            'document' => ['required_without:document_id', 'nullable', 'file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:'.StoreIdentityDocumentRequest::MAX_KB],
            'document_id' => ['required_without:document', 'nullable', 'string', 'size:26'],
            'employer_can_view' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required_without' => __('mobility.document.errors.file_required'),
            'document.mimetypes' => __('worker.credential.type_error'),
            'document.max' => __('worker.credential.size_error', ['max' => StoreIdentityDocumentRequest::MAX_KB / 1024]),
        ];
    }
}
