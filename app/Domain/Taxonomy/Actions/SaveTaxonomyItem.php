<?php

namespace App\Domain\Taxonomy\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Adds or renames a sector, occupation, specialization or skill (spec §11: Sector → Occupation →
 * Specialization → Skill). Names are per language; English is required and the others fall back
 * to it. The slug comes from the English name when the item is created and never changes, so
 * search filters and saved links keep working.
 */
class SaveTaxonomyItem
{
    public const TYPES = ['sector', 'occupation', 'specialization', 'skill'];

    /**
     * @param  array<string, string>  $names  locale => name
     */
    public function handle(string $type, ?Model $item, ?string $parentId, array $names, User $actor): Model
    {
        $names = array_filter(array_map('trim', $names), fn (string $name) => $name !== '');

        if ($item === null) {
            $slug = str($names['en'])->slug()->limit(60, '')->toString();
            $model = match ($type) {
                'sector' => new Sector(['slug' => $slug, 'sort_order' => (int) Sector::query()->max('sort_order') + 1]),
                'occupation' => new Occupation(['slug' => $slug, 'sector_id' => $parentId]),
                'specialization' => new Specialization(['slug' => $slug, 'occupation_id' => $parentId]),
                default => new Skill(['slug' => $slug, 'occupation_id' => $parentId]),
            };

            $taken = $type === 'specialization'
                ? Specialization::query()->where('occupation_id', $parentId)->where('slug', $slug)->exists()
                : $model->newQuery()->where('slug', $slug)->exists();

            if ($taken) {
                throw ValidationException::withMessages(['names.en' => __('taxonomy.errors.taken')]);
            }

            $item = $model;
        }

        $old = $item->exists ? $item->getAttribute('name') : null;
        $item->setAttribute('name', $names);
        $item->save();

        activity('taxonomy')->performedOn($item)->causedBy($actor)
            ->withProperties(['type' => $type, 'old' => $old, 'attributes' => $names])
            ->log($old === null ? 'taxonomy.created' : 'taxonomy.renamed');

        return $item;
    }
}
