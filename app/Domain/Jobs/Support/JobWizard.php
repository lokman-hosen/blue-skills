<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Config\Models\Country;
use App\Domain\Jobs\Enums\AgeRange;
use App\Domain\Jobs\Enums\ApplyMethod;
use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\ContractDuration;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\EducationLevel;
use App\Domain\Jobs\Enums\EmploymentType;
use App\Domain\Jobs\Enums\ExperienceLevel;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Enums\SalaryBasis;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Enums\ShiftPattern;
use App\Domain\Jobs\Enums\WorkerFees;
use App\Domain\Jobs\Enums\WorkingDays;
use App\Domain\Jobs\Enums\WorkingHours;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Taxonomy\Models\Occupation;
use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;

/**
 * The Post a Job wizard, following the universal job posting prototype (D-117): job basics; pay
 * and benefits; requirements; visa, contract and how to apply. Then preview and submit. Every
 * field is optional unless noted; an empty value shows "Not disclosed" on the Truth Card.
 */
final class JobWizard
{
    public const STEPS = ['basics', 'pay', 'requirements', 'apply'];

    /**
     * Step names before D-117, so saved links and bookmarks still open the right step.
     */
    public const LEGACY_STEPS = ['requirement' => 'basics', 'terms' => 'pay', 'skills' => 'requirements', 'mobility' => 'apply'];

    /**
     * Language levels offered in the wizard. Anything else is the poster's own words (e.g. "JLPT N4").
     */
    public const LANGUAGE_LEVELS = ['basic', 'communicative', 'fluent'];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(string $step, array $input): array
    {
        $currencies = Rule::in(self::currencies());

        return match ($step) {
            'basics' => [
                'title' => ['required', 'string', 'max:120'],
                'occupation_id' => ['required', 'string', Rule::exists('occupations', 'id')],
                'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
                'headcount' => ['required', 'integer', 'min:1', 'max:10000'],
                'destination_country_code' => ['required', 'string', Rule::exists('countries', 'code')->where('enabled_as_destination', true)],
                'destination_city' => ['nullable', 'string', 'max:120'],
                'joining_from' => ['nullable', 'date', 'after_or_equal:today'],
                'joining_to' => ['nullable', 'date', 'after_or_equal:joining_from'],
            ],
            'pay' => [
                'salary_amount' => ['nullable', 'required_with:salary_max_amount', 'integer', 'min:1', 'max:100000000'],
                'salary_max_amount' => ['nullable', 'integer', 'max:100000000', 'gte:salary_amount'],
                'salary_currency' => ['nullable', 'required_with:salary_amount,accommodation_cost_amount', 'string', 'size:3', $currencies],
                'salary_period' => ['nullable', 'required_with:salary_amount', Rule::enum(SalaryPeriod::class)],
                'salary_basis' => ['nullable', Rule::enum(SalaryBasis::class)],
                ...self::withOther('working_hours', WorkingHours::class),
                ...self::withOther('working_days', WorkingDays::class),
                ...self::withOther('shift_pattern', ShiftPattern::class),
                'overtime_terms' => ['nullable', 'string', 'max:500'],
                ...collect(JobPosting::BENEFITS)->flatMap(fn (string $benefit) => [
                    $benefit => ['nullable', Rule::enum(BenefitProvision::class)],
                    "{$benefit}_details" => ['nullable', 'string', 'max:255'],
                ])->all(),
                // Shown only when the worker pays for accommodation (prototype step 2).
                'accommodation_cost_amount' => ['nullable', Rule::requiredIf(($input['accommodation'] ?? null) === BenefitProvision::Deducted->value), 'integer', 'min:1', 'max:100000000'],
                'other_benefits' => ['nullable', 'string', 'max:1000'],
            ],
            'requirements' => [
                ...self::withOther('experience_level', ExperienceLevel::class, 'experience_other'),
                ...self::withOther('education_level', EducationLevel::class, 'education_other'),
                'language_requirements' => ['present', 'array', 'max:5'],
                'language_requirements.*.code' => ['required', 'string', 'regex:/^[a-z]{2,3}$/', 'distinct'],
                'language_requirements.*.level' => ['required', 'string', 'max:40'],
                ...self::withOther('age_range', AgeRange::class, 'age_other'),
                'skill_ids' => ['present', 'array', 'max:15'],
                'skill_ids.*' => ['string', 'distinct', Rule::exists('skills', 'id')],
                'other_skills' => ['nullable', 'string', 'max:500'],
                'duties' => ['nullable', 'string', 'max:5000'],
            ],
            'apply' => [
                'permit_payer' => ['nullable', Rule::enum(CostPayer::class)],
                'visa_payer' => ['nullable', Rule::enum(CostPayer::class)],
                ...self::withOther('contract_duration', ContractDuration::class, 'contract_other'),
                'worker_fees' => ['nullable', Rule::enum(WorkerFees::class), self::feesMatchCosts($input)],
                'costs' => ['present', 'array', 'max:20'],
                'costs.*.category' => ['required', Rule::enum(JobCostCategory::class)],
                'costs.*.description' => ['nullable', 'string', 'max:160'],
                'costs.*.amount' => ['required', 'integer', 'min:0', 'max:100000000'],
                'costs.*.currency' => ['required', 'string', 'size:3', $currencies],
                'costs.*.payer' => ['required', Rule::enum(CostPayer::class)],
                'apply_method' => ['required', Rule::enum(ApplyMethod::class)],
                'apply_url' => ['nullable', 'required_if:apply_method,'.ApplyMethod::Both->value, 'string', 'max:2048', 'url:https,http'],
                'deadline' => ['nullable', 'date', 'after_or_equal:today'],
                'additional_details' => ['nullable', 'string', 'max:10000'],
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
            'basics' => ['attributes' => [
                'title' => trim((string) $validated['title']),
                'occupation_id' => $validated['occupation_id'],
                'employment_type' => $validated['employment_type'] ?? null,
                'headcount' => (int) $validated['headcount'],
                'destination_country_code' => $validated['destination_country_code'],
                'destination_city' => self::text($validated['destination_city'] ?? null),
                'joining_from' => $validated['joining_from'] ?? null,
                'joining_to' => $validated['joining_to'] ?? null,
                // Agency jobs only; the request prohibits it for employers (D-077).
                ...(isset($validated['job_mandate_id']) ? ['job_mandate_id' => $validated['job_mandate_id']] : []),
            ]],
            'pay' => ['attributes' => self::payAttributes($validated)],
            'requirements' => [
                'attributes' => [
                    ...self::otherPair($validated, 'experience_level', 'experience_other'),
                    'min_experience_years' => isset($validated['experience_level'])
                        ? ExperienceLevel::from($validated['experience_level'])->minYears()
                        : null,
                    ...self::otherPair($validated, 'education_level', 'education_other'),
                    'language_requirements' => array_values(array_map(
                        fn (array $language) => ['code' => $language['code'], 'level' => trim((string) $language['level'])],
                        $validated['language_requirements'] ?? [],
                    )),
                    ...self::otherPair($validated, 'age_range', 'age_other'),
                    'other_skills' => self::text($validated['other_skills'] ?? null),
                    'duties' => self::text($validated['duties'] ?? null),
                ],
                'skill_ids' => array_values($validated['skill_ids'] ?? []),
            ],
            'apply' => [
                'attributes' => [
                    'permit_payer' => $validated['permit_payer'] ?? null,
                    'visa_payer' => $validated['visa_payer'] ?? null,
                    ...self::otherPair($validated, 'contract_duration', 'contract_other'),
                    'contract_months' => isset($validated['contract_duration'])
                        ? ContractDuration::from($validated['contract_duration'])->months()
                        : null,
                    'worker_fees' => $validated['worker_fees'] ?? null,
                    'apply_method' => $validated['apply_method'],
                    'apply_url' => $validated['apply_method'] === ApplyMethod::Both->value ? self::text($validated['apply_url'] ?? null) : null,
                    'deadline' => $validated['deadline'] ?? null,
                    'additional_details' => self::text($validated['additional_details'] ?? null),
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
     * Currencies a job may quote: the configured list plus every destination's own currency.
     *
     * @return list<string>
     */
    public static function currencies(): array
    {
        /** @var list<string> $configured */
        $configured = config('blueskills.job_currencies', []);

        return collect($configured)
            ->merge(Country::query()->distinct()->pluck('currency'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Abbreviation-safe default for a new job's code when the occupation is known.
     */
    public static function code(string $countryCode, string $occupationId): string
    {
        return JobCode::generate($countryCode, Occupation::query()->findOrFail($occupationId));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private static function payAttributes(array $validated): array
    {
        $salary = isset($validated['salary_amount']);
        $currency = isset($validated['salary_currency']) ? (string) $validated['salary_currency'] : null;
        $paysForAccommodation = ($validated['accommodation'] ?? null) === BenefitProvision::Deducted->value
            && isset($validated['accommodation_cost_amount']);

        return [
            'salary_amount_minor' => $salary ? Money::toMinor((int) $validated['salary_amount'], (string) $currency) : null,
            'salary_max_amount_minor' => $salary && isset($validated['salary_max_amount'])
                ? Money::toMinor((int) $validated['salary_max_amount'], (string) $currency)
                : null,
            // The currency also prices the accommodation cost, so keep it when either is given.
            'salary_currency' => $salary || $paysForAccommodation ? $currency : null,
            'salary_period' => $salary ? $validated['salary_period'] : null,
            'salary_basis' => $validated['salary_basis'] ?? null,
            ...self::otherPair($validated, 'working_hours', 'working_hours_other'),
            ...self::otherPair($validated, 'working_days', 'working_days_other'),
            ...self::otherPair($validated, 'shift_pattern', 'shift_pattern_other'),
            // Replaced by working_hours / working_days (D-117); cleared so they never go stale.
            'hours_per_day' => null,
            'days_per_week' => null,
            'overtime_terms' => self::text($validated['overtime_terms'] ?? null),
            ...collect(JobPosting::BENEFITS)->flatMap(fn (string $benefit) => [
                $benefit => $validated[$benefit] ?? null,
                "{$benefit}_details" => self::text($validated["{$benefit}_details"] ?? null),
            ])->all(),
            'accommodation_cost_amount_minor' => $paysForAccommodation
                ? Money::toMinor((int) $validated['accommodation_cost_amount'], (string) $currency)
                : null,
            'other_benefits' => self::text($validated['other_benefits'] ?? null),
        ];
    }

    /**
     * A select with an "Other" option and the poster's own words for it.
     *
     * @param  class-string<\BackedEnum>  $enum
     * @return array<string, list<mixed>>
     */
    private static function withOther(string $field, string $enum, ?string $otherField = null): array
    {
        return [
            $field => ['nullable', Rule::enum($enum)],
            $otherField ?? "{$field}_other" => ['nullable', "required_if:{$field},other", 'string', 'max:120'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private static function otherPair(array $validated, string $field, string $otherField): array
    {
        $value = $validated[$field] ?? null;

        return [
            $field => $value,
            $otherField => $value === 'other' ? self::text($validated[$otherField] ?? null) : null,
        ];
    }

    /**
     * The fee declaration must agree with the itemised costs (product rule 4): "Fees apply" needs
     * at least one worker-paid item, "None" allows no worker-paid item, visa or permit.
     *
     * @param  array<string, mixed>  $input
     */
    private static function feesMatchCosts(array $input): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($input): void {
            $costs = is_array($input['costs'] ?? null) ? $input['costs'] : [];
            $itemised = collect($costs)->contains(fn (mixed $cost) => is_array($cost) && ($cost['payer'] ?? null) === CostPayer::Worker->value);
            $workerPaysVisa = in_array(CostPayer::Worker->value, [$input['visa_payer'] ?? null, $input['permit_payer'] ?? null], true);

            if ($value === WorkerFees::Apply->value && ! $itemised) {
                $fail(__('jobs.validation.fees_need_items'));
            }

            if ($value === WorkerFees::None->value && ($itemised || $workerPaysVisa)) {
                $fail(__('jobs.validation.fees_none_but_items'));
            }
        };
    }

    private static function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
