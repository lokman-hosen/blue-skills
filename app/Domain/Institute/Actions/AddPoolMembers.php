<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\TalentPool;
use Illuminate\Validation\ValidationException;

/**
 * Adds graduates to a pool. Only workers who accepted one of the institute's cohorts can be
 * added; membership never moves ownership of a profile (spec §8, product rule 1).
 */
class AddPoolMembers
{
    /**
     * @param  list<string>  $profileIds
     */
    public function handle(TalentPool $pool, array $profileIds, User $actor): int
    {
        $eligible = self::eligibleProfileIds($pool);
        $invalid = array_diff($profileIds, $eligible);

        if ($invalid !== []) {
            throw ValidationException::withMessages(['profiles' => __('pools.errors.not_graduates')]);
        }

        $new = array_values(array_diff($profileIds, $pool->workers()->pluck('worker_profiles.id')->all()));
        $pool->workers()->attach(array_fill_keys($new, ['added_by' => $actor->id]));

        activity('institute')->performedOn($pool)->causedBy($actor)
            ->withProperties(['added' => $new])
            ->log('talent_pool.members_added');

        return count($new);
    }

    /**
     * Graduates who accepted one of the pool owner's cohorts.
     *
     * @return list<string>
     */
    public static function eligibleProfileIds(TalentPool $pool): array
    {
        return CohortMember::query()->accepted()
            ->whereHas('cohort', fn ($cohorts) => $cohorts->where('organization_id', $pool->organization_id))
            ->distinct()
            ->pluck('worker_profile_id')
            ->filter()
            ->values()
            ->all();
    }
}
