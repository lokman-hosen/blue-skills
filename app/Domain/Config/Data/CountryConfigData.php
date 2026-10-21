<?php

namespace App\Domain\Config\Data;

use App\Domain\Config\Models\Country;
use App\Domain\Config\Support\CountryRules;
use App\Domain\Identity\Models\User;
use Spatie\LaravelData\Data;

/**
 * A country in the configuration screens (prototype x-config).
 */
class CountryConfigData extends Data
{
    /**
     * @param  array<string, mixed>|null  $rules  Only on the country's own page.
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $currency,
        public bool $source,
        public bool $destination,
        public ?string $legalReviewedAt,
        public ?string $legalReviewedBy,
        public ?array $rules = null,
    ) {}

    public static function fromCountry(Country $country, bool $withRules = false): self
    {
        $reviewer = $country->legal_reviewed_by !== null ? User::query()->find($country->legal_reviewed_by) : null;

        return new self(
            code: $country->code,
            name: $country->name,
            currency: $country->currency,
            source: $country->enabled_as_source,
            destination: $country->enabled_as_destination,
            legalReviewedAt: $country->legal_reviewed_at?->toIso8601String(),
            legalReviewedBy: $reviewer?->name,
            rules: $withRules ? CountryRules::for($country) : null,
        );
    }
}
