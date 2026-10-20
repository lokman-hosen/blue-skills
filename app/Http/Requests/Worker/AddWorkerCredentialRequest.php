<?php

namespace App\Http\Requests\Worker;

use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Worker\Actions\AddWorkerCredential;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddWorkerCredentialRequest extends FormRequest
{
    public const MAX_KB = 10_240;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map(fn (EvidenceType $type) => $type->value, AddWorkerCredential::TYPES))],
            'title' => ['required', 'string', 'max:120'],
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
        ];
    }
}
