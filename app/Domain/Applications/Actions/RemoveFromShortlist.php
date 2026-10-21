<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Models\ShortlistEntry;
use App\Domain\Identity\Models\User;

class RemoveFromShortlist
{
    public function handle(ShortlistEntry $entry, User $actor): void
    {
        activity('shortlists')->performedOn($entry->shortlist)->causedBy($actor)
            ->withProperties(['worker' => $entry->workerProfile->reference])
            ->log('shortlist.entry_removed');

        $entry->delete();
    }
}
