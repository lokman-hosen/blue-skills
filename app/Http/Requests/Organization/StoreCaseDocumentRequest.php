<?php

namespace App\Http\Requests\Organization;

use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Verification\Enums\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseDocumentRequest extends FormRequest
{
    /** Organization and registration documents never belong on a worker's case. */
    public const EXCLUDED_TYPES = [DocumentType::TradeLicence, DocumentType::AgencyLicence, DocumentType::Accreditation];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DocumentType::class)->except(self::EXCLUDED_TYPES)],
            'title' => ['nullable', 'string', 'max:120'],
            'provided_by' => ['required', Rule::enum(DocumentProvider::class)],
            'due_on' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
