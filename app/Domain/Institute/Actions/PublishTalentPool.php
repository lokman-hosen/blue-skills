<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\TalentPool;
use Illuminate\Validation\ValidationException;

/**
 * Publishing lets verified employers and agencies browse the pool; they still see only members
 * who chose to be visible to employers (D-082). Only a verified institute can publish.
 */
class PublishTalentPool
{
    public function handle(TalentPool $pool, bool $publish, User $actor): void
    {
        if ($publish && ! $pool->organization->isVerified()) {
            throw ValidationException::withMessages(['pool' => __('pools.errors.not_verified')]);
        }

        if ($pool->isPublished() === $publish) {
            return;
        }

        $pool->update(['published_at' => $publish ? now() : null]);

        activity('institute')->performedOn($pool)->causedBy($actor)
            ->withProperties(['published' => $publish])
            ->log($publish ? 'talent_pool.published' : 'talent_pool.unpublished');
    }
}
