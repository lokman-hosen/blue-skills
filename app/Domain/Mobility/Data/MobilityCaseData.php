<?php

namespace App\Domain\Mobility\Data;

use App\Domain\Mobility\Enums\CaseClosedReason;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseEvent;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A mobility case for the worker ("Your journey") or for the organizations following it
 * (spec §6, prototype w-case and a-cases).
 */
class MobilityCaseData extends Data
{
    /**
     * @param  list<CaseStageData>  $stages
     * @param  list<CaseEventData>  $events
     */
    public function __construct(
        public string $id,
        public string $reference,
        /** Translated via `status.case_{value}`. */
        public StatusData $status,
        public ?CaseClosedReason $closedReason,
        public ?string $closedAt,
        public string $applicationId,
        public string $jobTitle,
        public string $jobCode,
        public string $destinationCountryCode,
        public string $destination,
        public string $employer,
        public ?string $agency,
        public string $managedBy,
        public string $handledBy,
        public ?string $workerName,
        public ?CaseStageData $current,
        public array $stages,
        public array $events,
        public string $openedAt,
    ) {}

    /**
     * @param  bool  $employerOnly  The viewer's organization follows an agency case: document events
     *                              for items not shared with the employer are left out (D-079).
     */
    public static function fromCase(MobilityCase $case, bool $forWorker, bool $employerOnly = false): self
    {
        $case->loadMissing(['application.job.destinationCountry', 'application.workerProfile.user', 'stages', 'events.actor', 'events.caseDocument', 'managingOrganization', 'employer', 'agency', 'handler']);
        $managedBy = $case->managingOrganization->display_name ?? $case->employer->display_name ?? '';
        $stages = $case->stages->map(fn (MobilityCaseStage $stage) => CaseStageData::fromStage($stage, $case->template))->values()->all();

        /** @var string $status */
        $status = $case->status->getValue();

        return new self(
            id: $case->id,
            reference: (string) $case->reference,
            status: new StatusData('case_'.$status, $case->status->label(), $case->status->tone()),
            closedReason: $case->closed_reason,
            closedAt: $case->closed_at?->toIso8601String(),
            applicationId: $case->application_id,
            jobTitle: $case->application->job->title,
            jobCode: $case->application->job->public_code,
            destinationCountryCode: $case->template,
            destination: $case->application->job->destinationCountry->name ?? $case->template,
            employer: $case->employer->display_name ?? '',
            agency: $case->agency?->display_name,
            managedBy: $managedBy,
            handledBy: $case->handler !== null ? "{$case->handler->name} · {$managedBy}" : $managedBy,
            workerName: $forWorker ? null : $case->application->workerProfile->user?->name,
            current: collect($stages)->first(fn (CaseStageData $stage) => $stage->key === $case->current_stage),
            stages: $stages,
            events: $case->events->reverse()
                ->reject(fn (MobilityCaseEvent $event) => $employerOnly && $event->caseDocument !== null && ! $event->caseDocument->employer_can_view)
                ->map(fn (MobilityCaseEvent $event) => CaseEventData::fromEvent($event, $case, $forWorker))->values()->all(),
            openedAt: $case->created_at?->toIso8601String() ?? '',
        );
    }
}
