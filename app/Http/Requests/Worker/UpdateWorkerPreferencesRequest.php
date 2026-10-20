<?php

namespace App\Http\Requests\Worker;

use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkerPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'passport_status' => ['required', Rule::enum(PassportStatus::class)],
            'passport_valid_until_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'availability' => ['required', Rule::enum(Availability::class)],
            'destinations' => ['present', 'array', 'max:5'],
            'destinations.*' => ['string', 'distinct', Rule::exists('countries', 'code')->where('enabled_as_destination', true)],
            'salary_amount' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'salary_currency' => ['required_with:salary_amount', 'nullable', 'string', 'size:3', Rule::exists('countries', 'currency')],
            'salary_period' => ['required_with:salary_amount', 'nullable', Rule::enum(SalaryPeriod::class)],
        ];
    }

    /**
     * @return array{passport_status: string, passport_valid_until_year: int|null, availability: string, destinations: list<string>, salary_amount: int|null, salary_currency: string|null, salary_period: string|null}
     */
    public function fields(): array
    {
        /** @var list<string> $destinations */
        $destinations = array_values($this->validated('destinations', []));

        return [
            'passport_status' => $this->string('passport_status')->toString(),
            'passport_valid_until_year' => $this->filled('passport_valid_until_year') ? $this->integer('passport_valid_until_year') : null,
            'availability' => $this->string('availability')->toString(),
            'destinations' => $destinations,
            'salary_amount' => $this->filled('salary_amount') ? $this->integer('salary_amount') : null,
            'salary_currency' => $this->filled('salary_amount') ? $this->string('salary_currency')->upper()->toString() : null,
            'salary_period' => $this->filled('salary_amount') ? $this->string('salary_period')->toString() : null,
        ];
    }
}
