<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Enums\AgeRange;
use App\Domain\Jobs\Enums\ApplyMethod;
use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\ContractDuration;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\EducationLevel;
use App\Domain\Jobs\Enums\EmploymentType;
use App\Domain\Jobs\Enums\ExperienceLevel;
use App\Domain\Jobs\Enums\SalaryBasis;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Enums\ShiftPattern;
use App\Domain\Jobs\Enums\WorkerFees;
use App\Domain\Jobs\Enums\WorkingDays;
use App\Domain\Jobs\Enums\WorkingHours;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobPosting;
use App\Support\Money;
use Spatie\LaravelData\Data;

/**
 * A job as the wizard edits it: amounts in whole units, benefits flattened (D-056, D-117).
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
        // Basics
        public string $title,
        public ?string $jobMandateId,
        public string $occupationId,
        public ?EmploymentType $employmentType,
        public int $headcount,
        public string $destinationCountryCode,
        public ?string $destinationCity,
        public ?string $joiningFrom,
        public ?string $joiningTo,
        // Pay and benefits
        public ?int $salaryAmount,
        public ?int $salaryMaxAmount,
        public ?string $salaryCurrency,
        public ?SalaryPeriod $salaryPeriod,
        public ?SalaryBasis $salaryBasis,
        public ?WorkingHours $workingHours,
        public ?string $workingHoursOther,
        public ?WorkingDays $workingDays,
        public ?string $workingDaysOther,
        public ?ShiftPattern $shiftPattern,
        public ?string $shiftPatternOther,
        public ?string $overtimeTerms,
        public array $benefits,
        public array $benefitDetails,
        public ?int $accommodationCostAmount,
        public ?string $otherBenefits,
        // Requirements
        public ?ExperienceLevel $experienceLevel,
        public ?string $experienceOther,
        public ?EducationLevel $educationLevel,
        public ?string $educationOther,
        public array $languageRequirements,
        public ?AgeRange $ageRange,
        public ?string $ageOther,
        public array $skillIds,
        public ?string $otherSkills,
        public ?string $duties,
        // Visa and apply
        public ?CostPayer $permitPayer,
        public ?CostPayer $visaPayer,
        public ?ContractDuration $contractDuration,
        public ?string $contractOther,
        public ?WorkerFees $workerFees,
        public array $costs,
        public ApplyMethod $applyMethod,
        public ?string $applyUrl,
        public ?string $deadline,
        public ?string $additionalDetails,
    ) {}

    public static function fromJob(JobPosting $job): self
    {
        $job->loadMissing(['skills', 'costs']);
        $whole = fn (?int $minor) => $minor !== null && $job->salary_currency !== null ? Money::toWhole($minor, $job->salary_currency) : null;

        return new self(
            title: $job->title,
            jobMandateId: $job->job_mandate_id,
            occupationId: $job->occupation_id,
            employmentType: $job->employment_type,
            headcount: $job->headcount,
            destinationCountryCode: $job->destination_country_code,
            destinationCity: $job->destination_city,
            joiningFrom: $job->joining_from?->toDateString(),
            joiningTo: $job->joining_to?->toDateString(),
            salaryAmount: $whole($job->salary_amount_minor),
            salaryMaxAmount: $whole($job->salary_max_amount_minor),
            salaryCurrency: $job->salary_currency,
            salaryPeriod: $job->salary_period,
            salaryBasis: $job->salary_basis,
            workingHours: $job->working_hours,
            workingHoursOther: $job->working_hours_other,
            workingDays: $job->working_days,
            workingDaysOther: $job->working_days_other,
            shiftPattern: $job->shift_pattern,
            shiftPatternOther: $job->shift_pattern_other,
            overtimeTerms: $job->overtime_terms,
            benefits: collect(JobPosting::BENEFITS)->mapWithKeys(fn (string $key) => [$key => $job->{$key}])->all(),
            benefitDetails: collect(JobPosting::BENEFITS)->mapWithKeys(fn (string $key) => [$key => $job->{"{$key}_details"}])->all(),
            accommodationCostAmount: $whole($job->accommodation_cost_amount_minor),
            otherBenefits: $job->other_benefits,
            experienceLevel: $job->experience_level,
            experienceOther: $job->experience_other,
            educationLevel: $job->education_level,
            educationOther: $job->education_other,
            languageRequirements: array_values(array_map(
                fn (array $requirement) => new LanguageRequirementData((string) $requirement['code'], (string) $requirement['level']),
                $job->language_requirements ?? [],
            )),
            ageRange: $job->age_range,
            ageOther: $job->age_other,
            skillIds: $job->skills->pluck('id')->values()->all(),
            otherSkills: $job->other_skills,
            duties: $job->duties,
            permitPayer: $job->permit_payer,
            visaPayer: $job->visa_payer,
            contractDuration: $job->contract_duration,
            contractOther: $job->contract_other,
            workerFees: $job->worker_fees,
            costs: $job->costs->map(fn (JobCost $cost) => new JobCostFormData(
                category: $cost->category,
                description: $cost->description,
                amount: Money::toWhole($cost->amount_minor, $cost->currency),
                currency: $cost->currency,
                payer: $cost->payer,
            ))->values()->all(),
            applyMethod: $job->apply_method ?? ApplyMethod::Blueskills,
            applyUrl: $job->apply_url,
            deadline: $job->deadline?->toDateString(),
            additionalDetails: $job->additional_details,
        );
    }
}
