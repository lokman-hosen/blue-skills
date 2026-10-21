<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Models\JobPosting;
use App\Support\Data\MoneyData;
use Spatie\LaravelData\Data;

/**
 * A job in search results: the headline facts from its Truth Card, nothing guessed.
 */
class JobListingData extends Data
{
    public function __construct(
        public string $code,
        public string $title,
        public string $employerName,
        public bool $employerVerified,
        public ?string $occupation,
        public string $destinationCountryCode,
        public ?string $destinationCountryName,
        public ?string $destinationCity,
        public ?MoneyData $salary,
        public ?SalaryPeriod $salaryPeriod,
        public ?BenefitProvision $accommodation,
        public bool $workerPaysCosts,
        public ?string $deadline,
        public ?string $publishedAt,
        public ?MatchSummaryData $match,
    ) {}

    public static function fromJob(JobPosting $job, ?MatchSummaryData $match = null): self
    {
        return new self(
            code: $job->public_code,
            title: $job->title,
            employerName: $job->employer->display_name,
            employerVerified: $job->employer->isVerified(),
            occupation: $job->occupation?->translatedName(),
            destinationCountryCode: $job->destination_country_code,
            destinationCountryName: $job->destinationCountry?->name,
            destinationCity: $job->destination_city,
            salary: $job->salary_amount_minor !== null && $job->salary_currency !== null
                ? new MoneyData($job->salary_amount_minor, $job->salary_currency)
                : null,
            salaryPeriod: $job->salary_period,
            accommodation: $job->accommodation,
            workerPaysCosts: $job->workerCosts()->isNotEmpty() || $job->visa_payer === CostPayer::Worker || $job->permit_payer === CostPayer::Worker,
            deadline: $job->deadline?->toDateString(),
            publishedAt: $job->published_at?->toIso8601String(),
            match: $match,
        );
    }
}
