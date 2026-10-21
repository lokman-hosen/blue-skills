<?php

namespace App\Domain\Applications\Support;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\ApplicationState;
use App\Domain\Identity\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

/**
 * Moves an application to another stage through its state machine and records the step in the
 * timeline and the activity log with actor, reason code and before/after values (CLAUDE.md).
 * Callers wrap it in a transaction with whatever else changes.
 */
final class StageChange
{
    /**
     * @param  class-string<ApplicationState>  $target
     */
    public static function apply(Application $application, string $target, ?User $actor, ?string $reasonCode = null, ?string $note = null): void
    {
        $from = (string) $application->status->getValue();

        try {
            $application->status->transitionTo($target);
        } catch (CouldNotPerformTransition) {
            throw ValidationException::withMessages(['status' => __('applications.stage.not_allowed')]);
        }

        $application->forceFill(['stage_changed_at' => now()])->save();

        $application->stageEvents()->create([
            'from_status' => $from,
            'to_status' => $target::getMorphClass(),
            'actor_user_id' => $actor?->id,
            'reason_code' => $reasonCode,
            'note' => $note,
        ]);

        activity('applications')->performedOn($application)->causedBy($actor)
            ->withProperties(['from' => $from, 'to' => $target::getMorphClass(), 'reason_code' => $reasonCode])
            ->log('application.stage_changed');
    }
}
