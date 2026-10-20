<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A period of work with either dates or a number of years.
 */
class SaveWorkerExperienceRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country_code' => ['required', 'string', Rule::exists('countries', 'code')],
            'role' => ['required', 'string', 'max:120'],
            'employer_name' => ['nullable', 'string', 'max:160'],
            'years' => ['required_without:started_on', 'nullable', 'integer', 'min:0', 'max:60'],
            'started_on' => ['required_without:years', 'nullable', 'date', 'before_or_equal:today'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'years.required_without' => __('worker.experience.years_or_dates'),
            'started_on.required_without' => __('worker.experience.years_or_dates'),
        ];
    }

    /**
     * @return array{country_code: string, role: string, employer_name: string|null, years: int|null, started_on: string|null, ended_on: string|null, description: string|null}
     */
    public function fields(): array
    {
        $nullable = fn (string $key): ?string => $this->filled($key) ? $this->string($key)->trim()->toString() : null;

        return [
            'country_code' => $this->string('country_code')->toString(),
            'role' => $this->string('role')->trim()->toString(),
            'employer_name' => $nullable('employer_name'),
            'years' => $this->filled('years') ? $this->integer('years') : null,
            'started_on' => $nullable('started_on'),
            'ended_on' => $this->filled('started_on') ? $nullable('ended_on') : null,
            'description' => $nullable('description'),
        ];
    }
}
