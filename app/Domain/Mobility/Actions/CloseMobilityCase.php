<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseClosedReason;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Cancelled;
use App\Domain\Mobility\States\MobilityCase\Completed;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Closes a case: completed when the worker joined (every stage done or skipped), cancelled for
 * any other reason, which needs a note when it is "other".
 */
class CloseMobilityCase
{
    public function handle(MobilityCase $case, CaseClosedReason $reason, ?string $note, User $actor): MobilityCase
    {
        if (! $case->isOpen()) {
            throw ValidationException::withMessages(['reason' => __('mobility.errors.closed')]);
        }

        $unfinished = $case->stages()->whereNotIn('status', [MobilityStageStatus::Done, MobilityStageStatus::Skipped])->exists();

        if ($reason === CaseClosedReason::Joined && $unfinished) {
            throw ValidationException::withMessages(['reason' => __('mobility.errors.stages_left')]);
        }

        DB::transaction(function () use ($case, $reason, $note, $actor): void {
            $case->status->transitionTo($reason === CaseClosedReason::Joined ? Completed::class : Cancelled::class);
            $case->update(['closed_reason' => $reason, 'closed_at' => now(), 'current_stage' => null]);
            $case->events()->create(['action' => CaseEventAction::Closed, 'actor_user_id' => $actor->id, 'note' => $note]);

            activity('mobility')->performedOn($case)->causedBy($actor)
                ->withProperties(['reason' => $reason->value, 'note' => $note])
                ->log('mobility_case.closed');
        });

        Notify::worker($case->application, NotificationEvent::CaseClosed, [
            'reference' => (string) $case->reference,
            'closed_reason' => $reason->value,
        ], route('worker.cases.show', $case, false));

        return $case;
    }
}
