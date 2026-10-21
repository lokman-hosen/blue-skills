<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\Support\NextStep;
use App\Domain\Jobs\Data\JobListingData;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

class WorkerApplicationListItemData extends Data
{
    public function __construct(
        public string $id,
        public JobListingData $job,
        public StatusData $status,
        public string $appliedAt,
        public string $nextStep,
        public ?string $upcomingInterviewAt,
        public bool $jobChanged,
        public bool $closed,
    ) {}

    public static function fromApplication(Application $application): self
    {
        $upcoming = $application->interviews->first(fn (Interview $interview) => $interview->isUpcoming());

        return new self(
            id: $application->id,
            job: JobListingData::fromJob($application->job),
            status: StatusData::fromState($application->status),
            appliedAt: $application->applied_at->toIso8601String(),
            nextStep: NextStep::for($application),
            upcomingInterviewAt: $upcoming?->scheduled_at->toIso8601String(),
            jobChanged: $application->hasUnseenJobChange(),
            closed: $application->isClosed(),
        );
    }
}
