<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Config\Models\Country;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
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
     * @param  list<MandateOptionData>  $mandates  Agencies only: usable mandates, plus the job's own.
     */
    public function __construct(
        public array $occupations,
        public array $skills,
        public array $destinations,
        public array $currencies,
        public array $mandates,
    ) {}

    public static function load(Organization $organization, ?JobPosting $job = null): self
    {
        $mandates = [];

        if ($organization->type === OrganizationType::Agency) {
            $usable = JobMandate::usableFor($organization);

            // Keep the job's mandate selectable even after it stopped being usable, so the
            // wizard can say why.
            if ($job?->mandate !== null && ! $usable->contains($job->mandate)) {
                $usable->prepend($job->mandate);
            }

            $mandates = $usable->map(fn (JobMandate $mandate) => MandateOptionData::fromMandate($mandate, $job))->values()->all();
        }

        return new self(
            occupations: OccupationOptionData::catalog(),
            skills: Skill::query()->orderBy('slug')->get()->map(fn (Skill $skill) => TaxonomyOptionData::fromModel($skill))->values()->all(),
            destinations: Country::query()->where('enabled_as_destination', true)->orderBy('name')->get()
                ->map(fn (Country $country) => DestinationOptionData::fromCountry($country))
                ->values()
                ->all(),
            currencies: Country::query()->distinct()->orderBy('currency')->pluck('currency')->values()->all(),
            mandates: $mandates,
        );
    }
}
