<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Taxonomy\Models\Occupation;
use App\Support\Money;
use Illuminate\Validation\Rule;

/**
 * The Post a Job wizard (prototype "Post job"): four editable steps, then preview and submit.
 * Every field is optional unless noted; an empty value shows "Not disclosed" on the Truth Card.
 */
final class JobWizard
{
    public const STEPS = ['requirement', 'skills', 'terms', 'mobility'];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(string $step, array $input): array
    {
        return match ($step) {
            'requirement' => [
                'title' => ['required', 'string', 'max:120'],
                'occupation_id' => ['required', 'string', Rule::exists('occupations', 'id')],
                'headcount' => ['required', 'integer', 'min:1', 'max:10000'],
                'destination_country_code' => ['required', 'string', Rule::exists('countries', 'code')->where('enabled_as_destination', true)],
                'destination_city' => ['nullable', 'string', 'max:120'],
                'joining_from' => ['nullable', 'date', 'after_or_equal:today'],
                'joining_to' => ['nullable', 'date', 'after_or_equal:joining_from'],
            ],
            'skills' => [
                'skill_ids' => ['present', 'array', 'max:15'],
                'skill_ids.*' => ['string', 'distinct', Rule::exists('skills', 'id')],
                'min_experience_years' => ['nullable', 'integer', 'min:0', 'max:40'],
                'language_requirements' => ['present', 'array', 'max:5'],
                'language_requirements.*.code' => ['required', 'string', 'regex:/^[a-z]{2,3}$/', 'distinct'],
                'language_requirements.*.level' => ['required', 'string', 'max:40'],
            ],
            'terms' => [
                'salary_amount' => ['nullable', 'integer', 'min:1', 'max:100000000'],
                'salary_currency' => ['required_with:salary_amount', 'nullable', 'string', 'size:3', Rule::exists('countries', 'currency')],
                'salary_period' => ['required_with:salary_amount', 'nullable', Rule::enum(SalaryPeriod::class)],
                'hours_per_day' => ['nullable', 'numeric', 'min:1', 'max:16'],
                'days_per_week' => ['nullable', 'integer', 'min:1', 'max:7'],
                'overtime_terms' => ['nullable', 'string', 'max:500'],
                'contract_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            ],
            'mobility' => [
                ...collect(JobPosting::BENEFITS)->flatMap(fn (string $benefit) => [
                    $benefit => ['nullable', Rule::enum(BenefitProvision::class)],
                    "{$benefit}_details" => ['nullable', 'string', 'max:255'],
                ])->all(),
                'other_benefits' => ['nullable', 'string', 'max:1000'],
                'permit_payer' => ['nullable', Rule::enum(CostPayer::class)],
                'visa_payer' => ['nullable', Rule::enum(CostPayer::class)],
                'deadline' => ['nullable', 'date', 'after_or_equal:today'],
                'costs' => ['present', 'array', 'max:20'],
                'costs.*.category' => ['required', Rule::enum(JobCostCategory::class)],
                'costs.*.description' => ['nullable', 'string', 'max:160'],
                'costs.*.amount' => ['required', 'integer', 'min:0', 'max:100000000'],
                'costs.*.currency' => ['required', 'string', 'size:3', Rule::exists('countries', 'currency')],
                'costs.*.payer' => ['required', Rule::enum(CostPayer::class)],
            ],
            default => [],
        };
    }

    /**
     * Validated step input → job attributes (money to minor units). Skills, languages and costs
     * are returned separately because they live in their own tables or columns.
     *
     * @param  array<string, mixed>  $validated
     * @return array{attributes: array<string, mixed>, skill_ids?: list<string>, costs?: list<array<string, mixed>>}
     */
    public static function normalise(string $step, array $validated): array
    {
        return match ($step) {
            'requirement' => ['attributes' => [
                'title' => trim((string) $validated['title']),
                'occupation_id' => $validated['occupation_id'],
                'headcount' => (int) $validated['headcount'],
                'destination_country_code' => $validated['destination_country_code'],
                'destination_city' => self::text($validated['destination_city'] ?? null),
                'joining_from' => $validated['joining_from'] ?? null,
                'joining_to' => $validated['joining_to'] ?? null,
            ]],
            'skills' => [
                'attributes' => [
                    'min_experience_years' => isset($validated['min_experience_years']) ? (int) $validated['min_experience_years'] : null,
                    'language_requirements' => array_values(array_map(
                        fn (array $language) => ['code' => $language['code'], 'level' => trim((string) $language['level'])],
                        $validated['language_requirements'] ?? [],
                    )),
                ],
                'skill_ids' => array_values($validated['skill_ids'] ?? []),
            ],
            'terms' => ['attributes' => [
                'salary_amount_minor' => isset($validated['salary_amount'])
                    ? Money::toMinor((int) $validated['salary_amount'], (string) $validated['salary_currency'])
                    : null,
                'salary_currency' => isset($validated['salary_amount']) ? $validated['salary_currency'] : null,
                'salary_period' => isset($validated['salary_amount']) ? $validated['salary_period'] : null,
                'hours_per_day' => $validated['hours_per_day'] ?? null,
                'days_per_week' => isset($validated['days_per_week']) ? (int) $validated['days_per_week'] : null,
                'overtime_terms' => self::text($validated['overtime_terms'] ?? null),
                'contract_months' => isset($validated['contract_months']) ? (int) $validated['contract_months'] : null,
            ]],
            'mobility' => [
                'attributes' => [
                    ...collect(JobPosting::BENEFITS)->flatMap(fn (string $benefit) => [
                        $benefit => $validated[$benefit] ?? null,
                        "{$benefit}_details" => self::text($validated["{$benefit}_details"] ?? null),
                    ])->all(),
                    'other_benefits' => self::text($validated['other_benefits'] ?? null),
                    'permit_payer' => $validated['permit_payer'] ?? null,
                    'visa_payer' => $validated['visa_payer'] ?? null,
                    'deadline' => $validated['deadline'] ?? null,
                ],
                'costs' => array_values(array_map(fn (array $cost) => [
                    'category' => $cost['category'],
                    'description' => self::text($cost['description'] ?? null),
                    'amount_minor' => Money::toMinor((int) $cost['amount'], (string) $cost['currency']),
                    'currency' => $cost['currency'],
                    'payer' => $cost['payer'],
                ], $validated['costs'] ?? [])),
            ],
            default => ['attributes' => []],
        };
    }

    /**
     * Abbreviation-safe default for a new job's code when the occupation is known.
     */
    public static function code(string $countryCode, string $occupationId): string
    {
        return JobCode::generate($countryCode, Occupation::query()->findOrFail($occupationId));
    }

    private static function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
