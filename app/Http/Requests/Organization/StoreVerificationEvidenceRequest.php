<?php

namespace App\Http\Requests\Organization;

use App\Domain\Verification\Models\VerificationRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the controller authorizes against the current organization
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimetypes:'.implode(',', VerificationRequest::EVIDENCE_MIME_TYPES),
                'max:'.VerificationRequest::EVIDENCE_MAX_KB,
            ],
        ];
    }
}
