<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobPosting;
use App\Support\Money;
use Spatie\LaravelData\Data;

/**
 * A job as the wizard edits it: amounts in whole units, benefits flattened.
 */
class JobFormData extends Data
{
    /**
     * @param  list<string>  $skillIds
     * @param  list<LanguageRequirementData>  $languageRequirements
     * @param  array<string, BenefitProvision|null>  $benefits
     * @param  array<string, string|null>  $benefitDetails
     * @param  list<JobCostFormData>  $costs
     */
    public function __construct(
        public string $title,
        public ?string $jobMandateId,
        public string $occupationId,
        public int $headcount,
        public string $destinationCountryCode,
        public ?string $destinationCity,
        public ?string $joiningFrom,
        public ?string $joiningTo,
        public array $skillIds,
        public ?int $minExperienceYears,
        public array $languageRequirements,
        public ?int $salaryAmount,
        public ?string $salaryCurrency,
        public ?SalaryPeriod $salaryPeriod,
        public ?float $hoursPerDay,
        public ?int $daysPerWeek,
        public ?string $overtimeTerms,
        public ?int $contractMonths,
        public array $benefits,
        public array $benefitDetails,
        public ?string $otherBenefits,
        public ?CostPayer $permitPayer,
        public ?CostPayer $visaPayer,
        public ?string $deadline,
        public array $costs,
    ) {}

    public static function fromJob(JobPosting $job): self
    {
        $job->loadMissing(['skills', 'costs']);

        return new self(
            title: $job->title,
            jobMandateId: $job->job_mandate_id,
            occupationId: $job->occupation_id,
            headcount: $job->headcount,
            destinationCountryCode: $job->destination_country_code,
            destinationCity: $job->destination_city,
            joiningFrom: $job->joining_from?->toDateString(),
            joiningTo: $job->joining_to?->toDateString(),
            skillIds: $job->skills->pluck('id')->values()->all(),
            minExperienceYears: $job->min_experience_years,
            languageRequirements: array_values(array_map(
                fn (array $requirement) => new LanguageRequirementData((string) $requirement['code'], (string) $requirement['level']),
                $job->language_requirements ?? [],
            )),
            salaryAmount: $job->salary_amount_minor !== null && $job->salary_currency !== null
                ? Money::toWhole($job->salary_amount_minor, $job->salary_currency)
                : null,
            salaryCurrency: $job->salary_currency,
            salaryPeriod: $job->salary_period,
            hoursPerDay: $job->hours_per_day !== null ? (float) $job->hours_per_day : null,
            daysPerWeek: $job->days_per_week,
            overtimeTerms: $job->overtime_terms,
            contractMonths: $job->contract_months,
            benefits: collect(JobPosting::BENEFITS)->mapWithKeys(fn (string $key) => [$key => $job->{$key}])->all(),
            benefitDetails: collect(JobPosting::BENEFITS)->mapWithKeys(fn (string $key) => [$key => $job->{"{$key}_details"}])->all(),
            otherBenefits: $job->other_benefits,
            permitPayer: $job->permit_payer,
            visaPayer: $job->visa_payer,
            deadline: $job->deadline?->toDateString(),
            costs: $job->costs->map(fn (JobCost $cost) => new JobCostFormData(
                category: $cost->category,
                description: $cost->description,
                amount: Money::toWhole($cost->amount_minor, $cost->currency),
                currency: $cost->currency,
                payer: $cost->payer,
            ))->values()->all(),
        );
    }
}
