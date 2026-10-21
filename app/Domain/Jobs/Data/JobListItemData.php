<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Verification\Data\ReviewDecisionData;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A job in its organization's own list, with the latest review outcome.
 */
class JobListItemData extends Data
{
    public function __construct(
        public string $code,
        public string $title,
        public ?string $occupation,
        public string $destinationCountryCode,
        public ?string $destinationCountryName,
        public ?string $destinationCity,
        public int $headcount,
        public StatusData $status,
        public ?string $deadline,
        public ?string $updatedAt,
        public ?string $publishedAt,
        public ?ReviewDecisionData $lastDecision,
        public bool $editable,
    ) {}

    public static function fromJob(JobPosting $job): self
    {
        $review = $job->verificationRequests->sortByDesc('created_at')->first();

        return new self(
            code: $job->public_code,
            title: $job->title,
            occupation: $job->occupation?->translatedName(),
            destinationCountryCode: $job->destination_country_code,
            destinationCountryName: $job->destinationCountry?->name,
            destinationCity: $job->destination_city,
            headcount: $job->headcount,
            status: StatusData::fromState($job->status),
            deadline: $job->deadline?->toDateString(),
            updatedAt: $job->updated_at?->toIso8601String(),
            publishedAt: $job->published_at?->toIso8601String(),
            lastDecision: $review !== null ? ReviewDecisionData::fromRequest($review) : null,
            editable: $job->isEditable(),
        );
    }
}
