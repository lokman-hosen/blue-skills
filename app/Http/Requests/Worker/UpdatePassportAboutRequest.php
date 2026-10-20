<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePassportAboutRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:120'],
            'occupation_id' => ['required', 'string', Rule::exists('occupations', 'id')],
            'specialization_id' => [
                'nullable', 'string',
                Rule::exists('specializations', 'id')->where('occupation_id', $this->string('occupation_id')->toString()),
            ],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'current_country_code' => ['required', 'string', Rule::exists('countries', 'code')],
        ];
    }

    /**
     * @return array{headline: string|null, occupation_id: string, specialization_id: string|null, years_experience: int, current_country_code: string}
     */
    public function fields(): array
    {
        return [
            'headline' => $this->filled('headline') ? $this->string('headline')->trim()->toString() : null,
            'occupation_id' => $this->string('occupation_id')->toString(),
            'specialization_id' => $this->filled('specialization_id') ? $this->string('specialization_id')->toString() : null,
            'years_experience' => $this->integer('years_experience'),
            'current_country_code' => $this->string('current_country_code')->toString(),
        ];
    }
}
