<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Domain\Mobility\Support\StageTemplate;
use Illuminate\Validation\ValidationException;

/**
 * What the worker should do for the current stage, and by when (prototype "View next action").
 */
class UpdateCaseNextAction
{
    public function handle(MobilityCase $case, MobilityCaseStage $stage, ?string $nextAction, ?string $dueOn, User $actor): void
    {
        if ($stage->key !== $case->current_stage) {
            throw ValidationException::withMessages(['stage' => __('mobility.errors.not_current')]);
        }

        $old = ['next_action' => $stage->next_action, 'due_on' => $stage->due_on?->toDateString()];
        $stage->update(['next_action' => $nextAction, 'due_on' => $dueOn]);

        if ($old === ['next_action' => $stage->next_action, 'due_on' => $stage->due_on?->toDateString()]) {
            return;
        }

        $case->events()->create(['action' => CaseEventAction::NextActionSet, 'stage_key' => $stage->key, 'actor_user_id' => $actor->id, 'note' => $nextAction]);

        activity('mobility')->performedOn($case)->causedBy($actor)
            ->withProperties(['stage' => $stage->key, 'old' => $old, 'attributes' => ['next_action' => $nextAction, 'due_on' => $dueOn]])
            ->log('mobility_case.next_action_set');

        if ($nextAction !== null) {
            Notify::worker($case->application, NotificationEvent::CaseUpdated, [
                'reference' => (string) $case->reference,
                'stage' => StageTemplate::label($case->template, $stage->key),
                'case_action' => 'next_action',
            ], route('worker.cases.show', $case, false));
        }
    }
}
