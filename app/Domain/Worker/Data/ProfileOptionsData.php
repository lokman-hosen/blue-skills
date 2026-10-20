<?php

namespace App\Domain\Worker\Data;

use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Config\Models\Country;
use App\Domain\Taxonomy\Data\OccupationOptionData;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Taxonomy\Models\Skill;
use Spatie\LaravelData\Data;

/**
 * Choices for editing Skill Passport fields: occupations with their specializations and
 * skills, every country (for work history) and the destinations jobs can be in.
 */
class ProfileOptionsData extends Data
{
    /**
     * @param  list<OccupationOptionData>  $occupations
     * @param  list<TaxonomyOptionData>  $skills  Every skill, including ones shared across occupations.
     * @param  list<CountryOptionData>  $countries
     * @param  list<CountryOptionData>  $destinations
     */
    public function __construct(
        public array $occupations,
        public array $skills,
        public array $countries,
        public array $destinations,
    ) {}

    public static function load(): self
    {
        $countries = Country::query()->orderBy('name')->get();

        return new self(
            occupations: OccupationOptionData::catalog(),
            skills: Skill::query()->orderBy('slug')->get()
                ->map(fn (Skill $skill) => TaxonomyOptionData::fromModel($skill))
                ->values()
                ->all(),
            countries: $countries->map(fn (Country $country) => CountryOptionData::fromCountry($country))->values()->all(),
            destinations: $countries->where('enabled_as_destination', true)
                ->map(fn (Country $country) => CountryOptionData::fromCountry($country))
                ->values()
                ->all(),
        );
    }
}
