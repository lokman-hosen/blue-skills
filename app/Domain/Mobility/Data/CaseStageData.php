<?php

namespace App\Domain\Mobility\Data;

use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Domain\Mobility\Support\StageTemplate;
use Spatie\LaravelData\Data;

/**
 * One stage of a case as StageTracker draws it.
 */
class CaseStageData extends Data
{
    public function __construct(
        public string $id,
        public string $key,
        public string $label,
        /** done | current | pending | blocked | skipped */
        public string $status,
        /** When it was completed, or when it is due for the current stage. */
        public ?string $date,
        public ?string $dueOn,
        public ?string $nextAction,
        public ?string $blockedReason,
    ) {}

    public static function fromStage(MobilityCaseStage $stage, string $countryCode): self
    {
        return new self(
            id: $stage->id,
            key: $stage->key,
            label: StageTemplate::label($countryCode, $stage->key),
            status: match ($stage->status) {
                MobilityStageStatus::Done => 'done',
                MobilityStageStatus::InProgress => 'current',
                MobilityStageStatus::Blocked => 'blocked',
                MobilityStageStatus::Skipped => 'skipped',
                MobilityStageStatus::Pending => 'pending',
            },
            date: $stage->completed_at?->toIso8601String() ?? $stage->due_on?->toDateString(),
            dueOn: $stage->due_on?->toDateString(),
            nextAction: $stage->next_action,
            blockedReason: $stage->blocked_reason,
        );
    }
}
