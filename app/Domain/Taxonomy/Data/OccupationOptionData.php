<?php

namespace App\Domain\Taxonomy\Data;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use Spatie\LaravelData\Data;

/**
 * An occupation as a worker picks it: with its sector, specializations and typical skills.
 */
class OccupationOptionData extends Data
{
    /**
     * @param  list<TaxonomyOptionData>  $specializations
     * @param  list<TaxonomyOptionData>  $skills
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public string $sector,
        public array $specializations,
        public array $skills,
    ) {}

    public static function fromOccupation(Occupation $occupation): self
    {
        return new self(
            id: $occupation->id,
            slug: $occupation->slug,
            name: $occupation->translatedName(),
            sector: $occupation->sector->translatedName(),
            specializations: $occupation->specializations
                ->map(fn (Specialization $specialization) => TaxonomyOptionData::fromModel($specialization))
                ->values()
                ->all(),
            skills: $occupation->skills
                ->map(fn (Skill $skill) => TaxonomyOptionData::fromModel($skill))
                ->values()
                ->all(),
        );
    }

    /**
     * Every occupation, grouped in sector order, ready for a picker.
     *
     * @return list<self>
     */
    public static function catalog(): array
    {
        return Occupation::query()
            ->with(['sector', 'specializations', 'skills'])
            ->join('sectors', 'sectors.id', '=', 'occupations.sector_id')
            ->orderBy('sectors.sort_order')
            ->orderBy('occupations.slug')
            ->select('occupations.*')
            ->get()
            ->map(fn (Occupation $occupation) => self::fromOccupation($occupation))
            ->values()
            ->all();
    }
}
