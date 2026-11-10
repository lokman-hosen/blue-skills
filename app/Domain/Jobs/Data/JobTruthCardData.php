<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Enums\AgeRange;
use App\Domain\Jobs\Enums\ApplyMethod;
use App\Domain\Jobs\Enums\ContractDuration;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\EducationLevel;
use App\Domain\Jobs\Enums\EmploymentType;
use App\Domain\Jobs\Enums\ExperienceLevel;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Enums\SalaryBasis;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Enums\ShiftPattern;
use App\Domain\Jobs\Enums\WorkerFees;
use App\Domain\Jobs\Enums\WorkingDays;
use App\Domain\Jobs\Enums\WorkingHours;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Closed;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Skill;
use App\Support\Data\MoneyData;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * The worker-facing summary rendered the same way everywhere (spec §5, product rule 4).
 * Every nullable field means "Not disclosed"; the client must never fill in a guess.
 */
class JobTruthCardData extends Data
{
    /**
     * @param  list<LanguageRequirementData>  $languageRequirements
     * @param  list<string>  $skills
     * @param  list<JobBenefitData>  $benefits
     * @param  list<JobCostData>  $costs
     * @param  list<TruthCardCheckData>  $checks
     */
    public function __construct(
        public string $publicCode,
        public string $title,
        public ?string $occupation,
        /** The occupation's sector (D-117). */
        public ?string $industry,
        public ?EmploymentType $employmentType,
        public string $destinationCountryCode,
        public ?string $destinationCountryName,
        public ?string $destinationCity,
        public string $employerName,
        public string $postedByName,
        public bool $postedByAgency,
        public ?string $agencyLicenceNumber,
        public ?string $agencyLicenceExpiresOn,
        public ?string $mandateReference,
        public ?int $mandateHeadcount,
        /** The minimum (or only) pay; `salaryMax` is set when pay is a range. */
        public ?MoneyData $salary,
        public ?MoneyData $salaryMax,
        public ?SalaryPeriod $salaryPeriod,
        public ?SalaryBasis $salaryBasis,
        public ?WorkingHours $workingHours,
        public ?string $workingHoursOther,
        public ?WorkingDays $workingDays,
        public ?string $workingDaysOther,
        public ?ShiftPattern $shiftPattern,
        public ?string $shiftPatternOther,
        /** Jobs saved before D-117 that were never edited since; read only when workingHours/Days are null. */
        public ?float $hoursPerDay,
        public ?int $daysPerWeek,
        public ?string $overtimeTerms,
        public ?ContractDuration $contractDuration,
        public ?string $contractOther,
        public ?int $contractMonths,
        public int $headcount,
        public ?ExperienceLevel $experienceLevel,
        public ?string $experienceOther,
        public ?int $minExperienceYears,
        public ?EducationLevel $educationLevel,
        public ?string $educationOther,
        public ?AgeRange $ageRange,
        public ?string $ageOther,
        public array $languageRequirements,
        public array $skills,
        public ?string $otherSkills,
        public ?string $duties,
        public array $benefits,
        /** Per month, in the salary currency; set only when the worker pays for accommodation. */
        public ?MoneyData $accommodationCost,
        public ?string $otherBenefits,
        public ?CostPayer $permitPayer,
        public ?CostPayer $visaPayer,
        public ?WorkerFees $workerFees,
        public array $costs,
        public ApplyMethod $applyMethod,
        public ?string $applyUrl,
        public ?string $additionalDetails,
        public ?string $joiningFrom,
        public ?string $joiningTo,
        public ?string $deadline,
        public StatusData $status,
        public array $checks,
    ) {}

    public static function fromJob(JobPosting $job): self
    {
        $job->loadMissing(['organization', 'employer', 'agency', 'mandate', 'occupation.sector', 'destinationCountry', 'costs', 'skills']);
        $agency = $job->agency;
        $money = fn (?int $minor) => $minor !== null && $job->salary_currency !== null ? new MoneyData($minor, $job->salary_currency) : null;

        return new self(
            publicCode: $job->public_code,
            title: $job->title,
            occupation: $job->occupation?->translatedName(),
            industry: $job->occupation?->sector?->translatedName(),
            employmentType: $job->employment_type,
            destinationCountryCode: $job->destination_country_code,
            destinationCountryName: $job->destinationCountry?->name,
            destinationCity: $job->destination_city,
            employerName: $job->employer->display_name,
            postedByName: $job->organization->display_name,
            postedByAgency: $job->organization->is($agency),
            agencyLicenceNumber: $agency?->licence_number,
            agencyLicenceExpiresOn: $agency?->licence_expires_on?->toDateString(),
            mandateReference: $job->mandate?->reference,
            mandateHeadcount: $job->mandate?->headcount,
            salary: $money($job->salary_amount_minor),
            salaryMax: $money($job->salary_max_amount_minor),
            salaryPeriod: $job->salary_period,
            salaryBasis: $job->salary_basis,
            workingHours: $job->working_hours,
            workingHoursOther: $job->working_hours_other,
            workingDays: $job->working_days,
            workingDaysOther: $job->working_days_other,
            shiftPattern: $job->shift_pattern,
            shiftPatternOther: $job->shift_pattern_other,
            hoursPerDay: $job->hours_per_day !== null ? (float) $job->hours_per_day : null,
            daysPerWeek: $job->days_per_week,
            overtimeTerms: $job->overtime_terms,
            contractDuration: $job->contract_duration,
            contractOther: $job->contract_other,
            contractMonths: $job->contract_months,
            headcount: $job->headcount,
            experienceLevel: $job->experience_level,
            experienceOther: $job->experience_other,
            minExperienceYears: $job->min_experience_years,
            educationLevel: $job->education_level,
            educationOther: $job->education_other,
            ageRange: $job->age_range,
            ageOther: $job->age_other,
            languageRequirements: array_values(array_map(
                fn (array $requirement) => new LanguageRequirementData((string) $requirement['code'], (string) $requirement['level']),
                $job->language_requirements ?? [],
            )),
            skills: $job->skills->map(fn (Skill $skill) => $skill->translatedName())->values()->all(),
            otherSkills: $job->other_skills,
            duties: $job->duties,
            benefits: array_map(fn (string $key) => new JobBenefitData(
                key: $key,
                provision: $job->{$key},
                details: $job->{"{$key}_details"},
            ), JobPosting::BENEFITS),
            accommodationCost: $money($job->accommodation_cost_amount_minor),
            otherBenefits: $job->other_benefits,
            permitPayer: $job->permit_payer,
            visaPayer: $job->visa_payer,
            workerFees: $job->worker_fees,
            costs: $job->costs->map(fn (JobCost $cost) => JobCostData::fromCost($cost))->values()->all(),
            applyMethod: $job->apply_method ?? ApplyMethod::Blueskills,
            applyUrl: $job->apply_method === ApplyMethod::Both ? $job->apply_url : null,
            additionalDetails: $job->additional_details,
            joiningFrom: $job->joining_from?->toDateString(),
            joiningTo: $job->joining_to?->toDateString(),
            deadline: $job->deadline?->toDateString(),
            status: StatusData::fromState($job->status),
            checks: self::checks($job, $agency),
        );
    }

    /**
     * @return list<TruthCardCheckData>
     */
    private static function checks(JobPosting $job, ?Organization $agency): array
    {
        $checks = [
            new TruthCardCheckData(
                $job->employer->isVerified() ? 'employer_verified' : 'employer_not_verified',
                $job->employer->isVerified() ? 'ok' : 'warn',
            ),
        ];

        if ($agency !== null) {
            $checks[] = match (true) {
                $agency->licence_expires_on?->isPast() === true => new TruthCardCheckData('agency_licence_expired', 'bad'),
                $agency->isVerified() => new TruthCardCheckData('agency_licence_verified', 'ok'),
                default => new TruthCardCheckData('agency_licence_unverified', 'warn'),
            };
        }

        if ($job->mandate !== null) {
            $checks[] = match ($job->mandate->status) {
                MandateStatus::Verified => new TruthCardCheckData('mandate_verified', 'ok'),
                MandateStatus::Pending => new TruthCardCheckData('mandate_pending', 'warn'),
                default => new TruthCardCheckData('mandate_not_valid', 'bad'),
            };
        }

        $checks[] = match (true) {
            // Paused and closed jobs were reviewed before they went live; a material change sends
            // a job back to review with its own status (product rule 5).
            $job->status->equals(Published::class, Paused::class, Closed::class) => new TruthCardCheckData('job_reviewed', 'ok'),
            $job->status->equals(Draft::class) => new TruthCardCheckData('job_not_reviewed', 'warn'),
            default => new TruthCardCheckData('job_under_review', 'warn'),
        };

        if ($job->salary_amount_minor !== null) {
            // Salary is declared by the employer; we never claim to have verified it.
            $checks[] = new TruthCardCheckData('salary_employer_declared', 'neutral');
        }

        return $checks;
    }
}
