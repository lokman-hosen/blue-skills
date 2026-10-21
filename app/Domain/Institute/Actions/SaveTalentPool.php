<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Organization\Models\Organization;

/**
 * Creates or edits an institute talent pool (spec §8).
 */
class SaveTalentPool
{
    /**
     * @param  array{name: string, description: string|null, occupation_id: string|null}  $data
     */
    public function handle(Organization $institute, ?TalentPool $pool, array $data, User $actor): TalentPool
    {
        $creating = $pool === null;
        $pool ??= new TalentPool(['organization_id' => $institute->id, 'created_by' => $actor->id]);
        $old = $creating ? [] : $pool->only(array_keys($data));
        $pool->fill($data)->save();

        activity('institute')->performedOn($pool)->causedBy($actor)
            ->withProperties(['old' => $old, 'attributes' => $data])
            ->log($creating ? 'talent_pool.created' : 'talent_pool.updated');

        return $pool;
    }
}
