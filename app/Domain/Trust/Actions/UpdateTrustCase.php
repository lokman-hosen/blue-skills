<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\CasePriority;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\States\TrustCase\Investigating;
use App\Domain\Trust\States\TrustCase\Open;

/**
 * Assign a case, change its priority, start investigating. The model logs before and after.
 */
class UpdateTrustCase
{
    public function handle(TrustCase $case, ?string $assigneeId, CasePriority $priority, bool $investigate, User $actor): void
    {
        $case->update(['assigned_to' => $assigneeId, 'priority' => $priority]);

        if ($investigate && $case->status->equals(Open::class)) {
            $case->status->transitionTo(Investigating::class);
        }

        activity('trust')->performedOn($case)->causedBy($actor)
            ->withProperties(['assigned_to' => $assigneeId, 'priority' => $priority->value, 'investigate' => $investigate])
            ->log('trust_case.updated');
    }
}
