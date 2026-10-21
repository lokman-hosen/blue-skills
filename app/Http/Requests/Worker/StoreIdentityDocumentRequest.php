<?php

namespace App\Http\Requests\Worker;

use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Worker\Actions\AddIdentityDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdentityDocumentRequest extends FormRequest
{
    public const MAX_KB = 10_240;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map(fn (DocumentType $type) => $type->value, AddIdentityDocument::TYPES))],
            'expires_on' => ['nullable', 'date', 'after:today'],
            'document' => ['required', 'file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:'.self::MAX_KB],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.mimetypes' => __('worker.credential.type_error'),
            'document.max' => __('worker.credential.size_error', ['max' => self::MAX_KB / 1024]),
            'expires_on.after' => __('documents.expired_error'),
        ];
    }
}
