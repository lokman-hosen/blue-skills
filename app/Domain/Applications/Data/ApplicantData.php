<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Applications\Models\ApplicationStageEvent;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\Models\Offer;
use App\Domain\Applications\States\Application\Offer as OfferStage;
use App\Domain\Applications\Support\ApplicantProjection;
use App\Domain\Applications\Support\ApplicationStages;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Data\SkillShowData;
use App\Domain\Worker\Support\MediaUrls;
use App\Support\Data\MoneyData;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * The organization's projection of one applicant (product rule 1): snapshot, live consented
 * items, history, interviews and offers.
 */
class ApplicantData extends Data
{
    /**
     * @param  list<ConsentItemData>  $consents
     * @param  list<StageEventData>  $events
     * @param  list<InterviewData>  $interviews
     * @param  list<OfferData>  $offers
     * @param  list<StageData>  $stages
     * @param  list<string>  $nextStages  Pipeline stages this applicant can be moved to now.
     */
    public function __construct(
        public string $id,
        public string $jobCode,
        public string $jobTitle,
        public StatusData $status,
        public bool $closed,
        public string $appliedAt,
        public ?string $snapshotTakenAt,
        public ?ProfileSnapshotData $snapshot,
        public ?SkillShowData $skillShow,
        public ?string $phone,
        public ?string $passportScanUrl,
        public array $consents,
        public array $stages,
        public array $events,
        public array $interviews,
        public array $offers,
        public ?MemberOptionData $assignee,
        public array $nextStages,
        public bool $canOffer,
        public ?MoneyData $jobSalary,
        public ?SalaryPeriod $jobSalaryPeriod,
        public ?string $mobilityCaseId,
    ) {}

    public static function fromApplication(Application $application): self
    {
        $application->loadMissing(['job', 'stageEvents.actor', 'interviews', 'offers', 'assignee']);
        $projection = new ApplicantProjection($application);
        $skillShow = $projection->skillShow();
        $scan = $projection->passportScan();
        $job = $application->job;

        return new self(
            id: $application->id,
            jobCode: $job->public_code,
            jobTitle: $job->title,
            status: StatusData::fromState($application->status),
            closed: $application->isClosed(),
            appliedAt: $application->applied_at->toIso8601String(),
            snapshotTakenAt: $application->snapshot?->taken_at?->toIso8601String(),
            snapshot: $projection->snapshot(),
            skillShow: $skillShow !== null ? SkillShowData::fromSkillShow($skillShow) : null,
            phone: $projection->phone(),
            passportScanUrl: $scan instanceof Document ? MediaUrls::signed($scan->getFirstMedia(Document::FILE_COLLECTION)) : null,
            consents: $application->consents->map(fn (ApplicationConsent $consent) => ConsentItemData::fromConsent($consent))->values()->all(),
            stages: ApplicationStages::for($application),
            events: $application->stageEvents->map(fn (ApplicationStageEvent $event) => StageEventData::forOrganization($event))->values()->all(),
            interviews: $application->interviews->map(fn (Interview $interview) => InterviewData::fromInterview($interview))->values()->all(),
            offers: $application->offers->map(fn (Offer $offer) => OfferData::fromOffer($offer))->values()->all(),
            assignee: $application->assignee !== null ? new MemberOptionData($application->assignee->id, $application->assignee->name) : null,
            nextStages: ApplicationStages::nextFor($application),
            canOffer: $application->openOffer() === null && $application->status->canTransitionTo(OfferStage::class),
            jobSalary: $job->salary_amount_minor !== null && $job->salary_currency !== null ? new MoneyData($job->salary_amount_minor, $job->salary_currency) : null,
            jobSalaryPeriod: $job->salary_period,
            mobilityCaseId: $application->mobilityCase?->id,
        );
    }
}
