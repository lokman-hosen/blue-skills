<?php

namespace App\Domain\Applications\Support;

use App\Domain\Applications\Enums\RejectionReason;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\ApplicationState;
use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
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

        self::notify($application, $target::getMorphClass(), $reasonCode);
    }

    /**
     * Stage alerts (D-060: in-app; WhatsApp, SMS and email from Module 5). Interviews and offers
     * alert from their own actions, which carry the details.
     */
    private static function notify(Application $application, string $to, ?string $reasonCode): void
    {
        match ($to) {
            'shortlisted' => Notify::worker($application, NotificationEvent::Shortlisted),
            'rejected' => Notify::worker($application, NotificationEvent::ApplicationRejected, ['reason' => $reasonCode ?? RejectionReason::Other->value]),
            'accepted', 'declined', 'withdrawn' => Notify::handlers($application, NotificationEvent::ApplicationAnswered, ['answer' => $to]),
            default => null,
        };
    }
}
