<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Enums\SalaryPeriod;
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
        public ?MoneyData $salary,
        public ?SalaryPeriod $salaryPeriod,
        public ?float $hoursPerDay,
        public ?int $daysPerWeek,
        public ?string $overtimeTerms,
        public ?int $contractMonths,
        public int $headcount,
        public ?int $minExperienceYears,
        public array $languageRequirements,
        public array $skills,
        public array $benefits,
        public ?string $otherBenefits,
        public ?CostPayer $permitPayer,
        public ?CostPayer $visaPayer,
        public array $costs,
        public ?string $joiningFrom,
        public ?string $joiningTo,
        public ?string $deadline,
        public StatusData $status,
        public array $checks,
    ) {}

    public static function fromJob(JobPosting $job): self
    {
        $job->loadMissing(['organization', 'employer', 'agency', 'mandate', 'occupation', 'destinationCountry', 'costs', 'skills']);
        $agency = $job->agency;

        return new self(
            publicCode: $job->public_code,
            title: $job->title,
            occupation: $job->occupation?->translatedName(),
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
            salary: $job->salary_amount_minor !== null && $job->salary_currency !== null
                ? new MoneyData($job->salary_amount_minor, $job->salary_currency)
                : null,
            salaryPeriod: $job->salary_period,
            hoursPerDay: $job->hours_per_day !== null ? (float) $job->hours_per_day : null,
            daysPerWeek: $job->days_per_week,
            overtimeTerms: $job->overtime_terms,
            contractMonths: $job->contract_months,
            headcount: $job->headcount,
            minExperienceYears: $job->min_experience_years,
            languageRequirements: array_values(array_map(
                fn (array $requirement) => new LanguageRequirementData((string) $requirement['code'], (string) $requirement['level']),
                $job->language_requirements ?? [],
            )),
            skills: $job->skills->map(fn (Skill $skill) => $skill->translatedName())->values()->all(),
            benefits: array_map(fn (string $key) => new JobBenefitData(
                key: $key,
                provision: $job->{$key},
                details: $job->{"{$key}_details"},
            ), JobPosting::BENEFITS),
            otherBenefits: $job->other_benefits,
            permitPayer: $job->permit_payer,
            visaPayer: $job->visa_payer,
            costs: $job->costs->map(fn (JobCost $cost) => JobCostData::fromCost($cost))->values()->all(),
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
