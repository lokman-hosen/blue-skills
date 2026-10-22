<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\CreditReason;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;

/**
 * A Finance Officer adds or takes away posting credits with a note (a goodwill credit, a
 * correction). Never edits an existing entry.
 */
class AdjustCredits
{
    public function handle(Organization $organization, int $delta, string $note, User $actor): CreditLedgerEntry
    {
        $entry = CreditLedgerEntry::query()->create([
            'organization_id' => $organization->id,
            'delta' => $delta,
            'reason' => CreditReason::Adjustment,
            'note' => $note,
            'actor_id' => $actor->id,
        ]);

        activity('billing')->performedOn($organization)->causedBy($actor)
            ->withProperties(['delta' => $delta, 'note' => $note])->log('credits.adjusted');

        return $entry;
    }
}
