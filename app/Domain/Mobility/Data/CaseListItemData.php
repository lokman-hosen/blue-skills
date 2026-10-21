<?php

namespace App\Domain\Mobility\Data;

use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Domain\Mobility\Support\StageTemplate;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * One case in an organization's mobility list (prototype a-cases): where it is, what is due and
 * what waits for the organization.
 */
class CaseListItemData extends Data
{
    /**
     * @param  list<string>  $progress  done | current | blocked | skipped | pending per stage, in order.
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $workerName,
        public string $jobTitle,
        public string $jobCode,
        public string $destinationCountryCode,
        /** Translated via `status.case_{value}`. */
        public StatusData $status,
        public ?string $stage,
        public bool $onHold,
        public array $progress,
        public ?string $dueOn,
        public bool $overdue,
        /** Shared files waiting for the managing organization's check (D-079). */
        public int $documentsToCheck,
        public string $managedBy,
        /** The viewer's organization follows the case but doesn't run it. */
        public bool $viewOnly,
    ) {}

    public static function fromCase(MobilityCase $case, bool $viewOnly): self
    {
        /** @var string $status */
        $status = $case->status->getValue();
        $current = $case->stages->firstWhere('key', $case->current_stage);

        return new self(
            id: $case->id,
            reference: (string) $case->reference,
            workerName: (string) $case->application->workerProfile->user?->name,
            jobTitle: $case->application->job->title,
            jobCode: $case->application->job->public_code,
            destinationCountryCode: $case->template,
            status: new StatusData('case_'.$status, $case->status->label(), $case->status->tone()),
            stage: $current instanceof MobilityCaseStage ? StageTemplate::label($case->template, $current->key) : null,
            onHold: $current?->status === MobilityStageStatus::Blocked,
            progress: $case->stages->map(fn (MobilityCaseStage $stage) => CaseStageData::fromStage($stage, $case->template)->status)->values()->all(),
            dueOn: $current?->due_on?->toDateString(),
            overdue: $current?->due_on !== null && $current->due_on->isBefore(now()->startOfDay()),
            documentsToCheck: $viewOnly ? 0 : $case->documents
                ->filter(fn (CaseDocument $item) => $item->fromWorker() && $item->status === CaseDocumentStatus::Uploaded && $item->isShared())
                ->count(),
            managedBy: (string) $case->managingOrganization?->display_name,
            viewOnly: $viewOnly,
        );
    }
}
