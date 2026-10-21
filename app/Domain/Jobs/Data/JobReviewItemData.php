<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Verification\Models\VerificationRequest;
use App\Support\Data\StatusData;
use LogicException;
use Spatie\LaravelData\Data;

/**
 * A job waiting in the staff review queue.
 */
class JobReviewItemData extends Data
{
    public function __construct(
        public string $reviewId,
        public StatusData $reviewStatus,
        public string $code,
        public string $title,
        public string $employerName,
        public StatusData $employerVerification,
        public ?string $destination,
        /** Why a live job came back, e.g. "Material change: salary_amount_minor". */
        public ?string $notes,
        public int $problemCount,
        public ?string $submittedAt,
    ) {}

    public static function fromRequest(VerificationRequest $review): self
    {
        $job = $review->subject;

        if (! $job instanceof JobPosting) {
            throw new LogicException('A job review must have a job subject.');
        }

        return new self(
            reviewId: $review->id,
            reviewStatus: StatusData::fromState($review->status),
            code: $job->public_code,
            title: $job->title,
            employerName: $job->employer->display_name,
            employerVerification: StatusData::fromState($job->employer->verification_status),
            destination: collect([$job->destination_city, $job->destinationCountry?->name])->filter()->implode(', ') ?: null,
            notes: $review->notes,
            problemCount: count(JobCompliance::problems($job)),
            submittedAt: $review->created_at?->toIso8601String(),
        );
    }
}
