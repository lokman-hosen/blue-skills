<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Models\Shortlist;
use App\Domain\Applications\Models\ShortlistEntry;
use App\Domain\Applications\Support\TalentAccess;
use App\Domain\Identity\Models\User;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Validation\ValidationException;

/**
 * Adds a worker who can be found in talent search to a list. The list gives no extra access to
 * the profile, and the entry stops showing details if the worker hides their profile (spec §8).
 */
class AddToShortlist
{
    public function handle(Shortlist $shortlist, WorkerProfile $profile, ?string $note, User $actor): ShortlistEntry
    {
        if (! TalentAccess::canView($shortlist->organization, $profile)) {
            throw ValidationException::withMessages(['worker' => __('shortlists.not_visible')]);
        }

        if ($shortlist->entries()->where('worker_profile_id', $profile->id)->exists()) {
            throw ValidationException::withMessages(['worker' => __('shortlists.already_on_list')]);
        }

        /** @var ShortlistEntry $entry */
        $entry = $shortlist->entries()->create(['worker_profile_id' => $profile->id, 'note' => $note, 'added_by' => $actor->id]);

        activity('shortlists')->performedOn($shortlist)->causedBy($actor)
            ->withProperties(['worker' => $profile->reference])
            ->log('shortlist.entry_added');

        return $entry;
    }
}
