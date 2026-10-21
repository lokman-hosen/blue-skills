<?php

namespace App\Domain\Taxonomy\Data;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Taxonomy\Support\TaxonomyUsage;
use Spatie\LaravelData\Data;

/**
 * A taxonomy item in the editor, with its names per language and how much uses it.
 */
class TaxonomyItemData extends Data
{
    /**
     * @param  array<string, string>  $names
     * @param  list<TaxonomyItemData>  $children
     */
    public function __construct(
        public string $id,
        /** sector | occupation | specialization | skill */
        public string $type,
        public string $slug,
        public array $names,
        public int $usage,
        public array $children = [],
    ) {}

    /**
     * @param  list<TaxonomyItemData>  $children
     */
    public static function fromItem(Sector|Occupation|Specialization|Skill $item, array $children = []): self
    {
        return new self(
            id: $item->id,
            type: match (true) {
                $item instanceof Sector => 'sector',
                $item instanceof Occupation => 'occupation',
                $item instanceof Specialization => 'specialization',
                default => 'skill',
            },
            slug: $item->slug,
            names: $item->name,
            usage: TaxonomyUsage::count($item),
            children: $children,
        );
    }
}
