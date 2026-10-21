<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Models\Shortlist;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteShortlist
{
    public function handle(Shortlist $shortlist, User $actor): void
    {
        DB::transaction(function () use ($shortlist, $actor): void {
            activity('shortlists')->performedOn($shortlist->organization)->causedBy($actor)
                ->withProperties(['shortlist' => $shortlist->id, 'name' => $shortlist->name, 'entries' => $shortlist->entries()->count()])
                ->log('shortlist.deleted');

            $shortlist->delete();
        });
    }
}
