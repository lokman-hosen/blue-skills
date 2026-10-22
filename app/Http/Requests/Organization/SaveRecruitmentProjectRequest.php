<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveRecruitmentProjectRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'target_headcount' => ['required', 'integer', 'min:1', 'max:100000'],
            'destination_country_code' => ['nullable', 'string', 'size:2', 'exists:countries,code'],
            'source_country_codes' => ['array', 'max:20'],
            'source_country_codes.*' => ['string', 'size:2', 'exists:countries,code'],
            'target_date' => ['nullable', 'date'],
            'job_ids' => ['array', 'max:100'],
            'job_ids.*' => ['string', 'ulid'],
            'member_ids' => ['array', 'max:100'],
            'member_ids.*' => ['string', 'ulid'],
        ];
    }

    /**
     * @return array{name: string, target_headcount: int, destination_country_code: ?string, source_country_codes: list<string>, target_date: ?string}
     */
    public function project(): array
    {
        /** @var list<string> $sources */
        $sources = array_values(array_unique(array_map('strval', $this->array('source_country_codes'))));

        return [
            'name' => $this->string('name')->trim()->toString(),
            'target_headcount' => $this->integer('target_headcount'),
            'destination_country_code' => $this->string('destination_country_code')->toString() ?: null,
            'source_country_codes' => $sources,
            'target_date' => $this->string('target_date')->toString() ?: null,
        ];
    }

    /**
     * @return list<string>
     */
    public function ids(string $key): array
    {
        return array_values(array_unique(array_map('strval', $this->array($key))));
    }
}
