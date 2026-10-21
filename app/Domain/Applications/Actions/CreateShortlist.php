<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Models\Shortlist;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use Illuminate\Validation\ValidationException;

class CreateShortlist
{
    public function handle(Organization $organization, string $name, ?JobPosting $job, User $actor): Shortlist
    {
        if ($job !== null && $job->organization_id !== $organization->id) {
            throw ValidationException::withMessages(['job' => __('shortlists.job_not_yours')]);
        }

        /** @var Shortlist $shortlist */
        $shortlist = $organization->shortlists()->create(['name' => $name, 'job_id' => $job?->id, 'created_by' => $actor->id]);

        activity('shortlists')->performedOn($shortlist)->causedBy($actor)->withProperties(['name' => $name])->log('shortlist.created');

        return $shortlist;
    }
}
