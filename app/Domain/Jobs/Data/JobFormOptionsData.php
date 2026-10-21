<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Config\Models\Country;
use App\Domain\Taxonomy\Data\OccupationOptionData;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Taxonomy\Models\Skill;
use Spatie\LaravelData\Data;

class JobFormOptionsData extends Data
{
    /**
     * @param  list<OccupationOptionData>  $occupations
     * @param  list<TaxonomyOptionData>  $skills
     * @param  list<DestinationOptionData>  $destinations
     * @param  list<string>  $currencies
     */
    public function __construct(
        public array $occupations,
        public array $skills,
        public array $destinations,
        public array $currencies,
    ) {}

    public static function load(): self
    {
        return new self(
            occupations: OccupationOptionData::catalog(),
            skills: Skill::query()->orderBy('slug')->get()->map(fn (Skill $skill) => TaxonomyOptionData::fromModel($skill))->values()->all(),
            destinations: Country::query()->where('enabled_as_destination', true)->orderBy('name')->get()
                ->map(fn (Country $country) => DestinationOptionData::fromCountry($country))
                ->values()
                ->all(),
            currencies: Country::query()->distinct()->orderBy('currency')->pluck('currency')->values()->all(),
        );
    }
}
