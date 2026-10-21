<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseClosedReason;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Domain\Mobility\Support\StageTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves the case's current stage: complete or skip it (the next stage starts), block it with a
 * reason, or resume it. Stages run in template order. Finishing the last stage closes the case
 * as joined (D-078).
 */
class ChangeCaseStage
{
    public const ACTIONS = ['complete', 'skip', 'block', 'resume'];

    public function __construct(private readonly CloseMobilityCase $close) {}

    public function handle(MobilityCase $case, MobilityCaseStage $stage, string $action, ?string $note, User $actor): MobilityCase
    {
        if ($stage->key !== $case->current_stage) {
            throw ValidationException::withMessages(['stage' => __('mobility.errors.not_current')]);
        }

        $allowed = match ($action) {
            'complete', 'skip', 'block' => $stage->status === MobilityStageStatus::InProgress,
            'resume' => $stage->status === MobilityStageStatus::Blocked,
            default => false,
        };

        if (! $allowed) {
            throw ValidationException::withMessages(['stage' => __('mobility.errors.not_allowed')]);
        }

        $before = $stage->status;

        DB::transaction(function () use ($case, $stage, $action, $note, $actor): void {
            match ($action) {
                'complete' => $stage->update(['status' => MobilityStageStatus::Done, 'completed_at' => now(), 'completed_by' => $actor->id, 'blocked_reason' => null]),
                'skip' => $stage->update(['status' => MobilityStageStatus::Skipped, 'completed_at' => now(), 'completed_by' => $actor->id]),
                'block' => $stage->update(['status' => MobilityStageStatus::Blocked, 'blocked_reason' => $note]),
                'resume' => $stage->update(['status' => MobilityStageStatus::InProgress, 'blocked_reason' => null]),
            };

            $case->events()->create([
                'action' => match ($action) {
                    'complete' => CaseEventAction::StageCompleted,
                    'skip' => CaseEventAction::StageSkipped,
                    'block' => CaseEventAction::StageBlocked,
                    'resume' => CaseEventAction::StageResumed,
                },
                'stage_key' => $stage->key,
                'actor_user_id' => $actor->id,
                'note' => $note,
            ]);

            if (in_array($action, ['complete', 'skip'], true)) {
                $next = $case->stages()->where('position', '>', $stage->position)->where('status', MobilityStageStatus::Pending)->first();
                $next?->update(['status' => MobilityStageStatus::InProgress]);
                $case->update(['current_stage' => $next?->key]);

                if ($next !== null) {
                    $case->events()->create(['action' => CaseEventAction::StageStarted, 'stage_key' => $next->key, 'actor_user_id' => $actor->id]);
                }
            }
        });

        activity('mobility')->performedOn($case)->causedBy($actor)
            ->withProperties(['stage' => $stage->key, 'action' => $action, 'old' => ['status' => $before->value], 'attributes' => ['status' => $stage->status->value], 'note' => $note])
            ->log("mobility_case.stage_{$action}");

        $case->unsetRelation('stages');

        if ($case->current_stage === null) {
            return $this->close->handle($case, CaseClosedReason::Joined, null, $actor);
        }

        Notify::worker($case->application, NotificationEvent::CaseUpdated, [
            'reference' => (string) $case->reference,
            'stage' => StageTemplate::label($case->template, $stage->key),
            'case_action' => $action,
        ], route('worker.cases.show', $case, false));

        return $case;
    }
}
