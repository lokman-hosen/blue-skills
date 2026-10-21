<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\TalentPool;
use Illuminate\Support\Facades\DB;

/**
 * The graduate leaves an institute's cohort (product rule 11): the institute stops seeing them
 * and their progress, and they leave its talent pools. Attestations already issued stay on the
 * worker's Skill Passport, where the worker can hide them.
 */
class LeaveCohort
{
    public function handle(CohortMember $member): void
    {
        DB::transaction(function () use ($member): void {
            $member->update(['status' => CohortMemberStatus::Left, 'shares_progress' => false, 'left_at' => now()]);

            $profileId = $member->worker_profile_id;
            $stillLinked = CohortMember::query()->accepted()
                ->where('worker_profile_id', $profileId)
                ->whereHas('cohort', fn ($cohorts) => $cohorts->where('organization_id', $member->cohort->organization_id))
                ->exists();

            if (! $stillLinked) {
                TalentPool::query()->where('organization_id', $member->cohort->organization_id)->get()
                    ->each(fn (TalentPool $pool) => $pool->workers()->detach($profileId));
            }
        });
    }
}
