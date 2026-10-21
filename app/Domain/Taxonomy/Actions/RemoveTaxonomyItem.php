<?php

namespace App\Domain\Taxonomy\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Taxonomy\Support\TaxonomyUsage;
use Illuminate\Validation\ValidationException;

/**
 * Removes a taxonomy item nobody uses yet (D-093). Anything in use stays; rename it instead.
 */
class RemoveTaxonomyItem
{
    public function handle(Sector|Occupation|Specialization|Skill $item, User $actor): void
    {
        if (TaxonomyUsage::count($item) > 0) {
            throw ValidationException::withMessages(['item' => __('taxonomy.errors.in_use')]);
        }

        activity('taxonomy')->performedOn($item)->causedBy($actor)
            ->withProperties(['slug' => $item->slug, 'name' => $item->name])
            ->log('taxonomy.removed');

        $item->delete();
    }
}
