<?php

namespace App\Http\Requests\Organization;

use App\Domain\Verification\Models\VerificationRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMandateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employer_id' => ['required', 'string', 'exists:organizations,id'],
            'occupation_ids' => ['required', 'array', 'min:1', 'max:10'],
            'occupation_ids.*' => ['string', 'exists:occupations,id'],
            'destination_country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'headcount' => ['required', 'integer', 'min:1', 'max:10000'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['required', 'date', 'after:today', 'after_or_equal:valid_from'],
            'letter' => ['required', 'file', 'mimetypes:'.implode(',', VerificationRequest::EVIDENCE_MIME_TYPES), 'max:'.VerificationRequest::EVIDENCE_MAX_KB],
        ];
    }

    /**
     * @return array{occupation_ids: list<string>, destination_country_code: string, headcount: int, valid_from: ?string, valid_until: string}
     */
    public function scope(): array
    {
        /** @var list<string> $occupations */
        $occupations = array_values(array_unique($this->array('occupation_ids')));

        return [
            'occupation_ids' => $occupations,
            'destination_country_code' => $this->string('destination_country_code')->upper()->toString(),
            'headcount' => $this->integer('headcount'),
            'valid_from' => $this->filled('valid_from') ? $this->string('valid_from')->toString() : null,
            'valid_until' => $this->string('valid_until')->toString(),
        ];
    }
}
