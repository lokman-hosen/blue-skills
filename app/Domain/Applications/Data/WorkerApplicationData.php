<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Applications\Models\ApplicationStageEvent;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\Models\Offer;
use App\Domain\Applications\Support\ApplicationStages;
use App\Domain\Applications\Support\NextStep;
use App\Domain\Jobs\Data\JobListingData;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * The worker's own view of one application: status, who handles it, next step, history,
 * interviews, offers and what is shared (spec §6, deck slide 12).
 */
class WorkerApplicationData extends Data
{
    /**
     * @param  list<StageData>  $stages
     * @param  list<StageEventData>  $events
     * @param  list<InterviewData>  $interviews
     * @param  list<OfferData>  $offers
     * @param  list<ConsentItemData>  $consents
     */
    public function __construct(
        public string $id,
        public JobListingData $job,
        public StatusData $status,
        public string $appliedAt,
        public bool $closed,
        public string $handledBy,
        public string $nextStep,
        public array $stages,
        public array $events,
        public array $interviews,
        public array $offers,
        public array $consents,
        public ?string $jobChangedAt,
        public ?string $snapshotTakenAt,
        public ?string $mobilityCaseId,
    ) {}

    public static function fromApplication(Application $application): self
    {
        $application->loadMissing(['job.employer', 'job.organization', 'stageEvents', 'interviews', 'offers', 'consents', 'assignee', 'snapshot']);

        return new self(
            id: $application->id,
            job: JobListingData::fromJob($application->job),
            status: StatusData::fromState($application->status),
            appliedAt: $application->applied_at->toIso8601String(),
            closed: $application->isClosed(),
            handledBy: $application->assignee !== null
                ? "{$application->assignee->name} · {$application->job->organization->display_name}"
                : $application->job->organization->display_name,
            nextStep: NextStep::for($application),
            stages: ApplicationStages::for($application),
            events: $application->stageEvents->map(fn (ApplicationStageEvent $event) => StageEventData::forWorker($event))->values()->all(),
            interviews: $application->interviews->map(fn (Interview $interview) => InterviewData::fromInterview($interview))->values()->all(),
            offers: $application->offers->map(fn (Offer $offer) => OfferData::fromOffer($offer))->values()->all(),
            consents: $application->consents
                ->sortBy(fn (ApplicationConsent $consent) => array_search($consent->item, ApplicationConsentItem::cases(), true))
                ->map(fn (ApplicationConsent $consent) => ConsentItemData::fromConsent($consent))
                ->values()
                ->all(),
            jobChangedAt: $application->hasUnseenJobChange() ? $application->job->material_change_at?->toIso8601String() : null,
            snapshotTakenAt: $application->snapshot?->taken_at?->toIso8601String(),
            mobilityCaseId: $application->mobilityCase?->id,
        );
    }
}
