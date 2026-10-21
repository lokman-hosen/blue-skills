<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\Support\ApplicantProjection;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * One row in a job's applicant pipeline, from the snapshot. Never a phone number or document.
 */
class ApplicantListItemData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $reference,
        public ?string $occupation,
        public ?int $yearsExperience,
        public ?string $currentCountry,
        public StatusData $status,
        public string $appliedAt,
        public int $met,
        public int $total,
        public bool $hasSkillShow,
        public ?string $assigneeName,
        public ?string $upcomingInterviewAt,
    ) {}

    public static function fromApplication(Application $application): self
    {
        $projection = new ApplicantProjection($application);
        $snapshot = $projection->snapshot();
        $upcoming = $application->interviews->first(fn (Interview $interview) => $interview->isUpcoming());

        return new self(
            id: $application->id,
            name: $snapshot->name ?? $application->workerProfile->user->name,
            reference: $snapshot->reference ?? $application->workerProfile->reference,
            occupation: $snapshot?->occupation?->name,
            yearsExperience: $snapshot?->yearsExperience,
            currentCountry: $snapshot?->currentCountry?->name,
            status: StatusData::fromState($application->status),
            appliedAt: $application->applied_at->toIso8601String(),
            met: $snapshot->match->met ?? 0,
            total: $snapshot->match->total ?? 0,
            hasSkillShow: $projection->shares(ApplicationConsentItem::SkillShow) && $application->skill_show_id !== null,
            assigneeName: $application->assignee?->name,
            upcomingInterviewAt: $upcoming?->scheduled_at->toIso8601String(),
        );
    }
}
