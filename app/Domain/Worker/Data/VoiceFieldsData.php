<?php

namespace App\Domain\Worker\Data;

use Spatie\LaravelData\Data;

/**
 * Fields understood from (or confirmed for) one Voice-to-CV answer. Only the question's own
 * fields are set; null means "not understood", which the client shows as a gap to fill.
 */
class VoiceFieldsData extends Data
{
    /**
     * @param  list<VoiceCountryData>|null  $countries
     * @param  list<string>|null  $skills  Skill slugs.
     * @param  list<string>|null  $destinations  Country codes in order of preference.
     */
    public function __construct(
        public ?string $occupation = null,
        public ?string $specialization = null,
        public ?int $yearsExperience = null,
        public ?array $countries = null,
        public ?array $skills = null,
        public ?string $passportStatus = null,
        public ?int $passportValidUntilYear = null,
        public ?string $availability = null,
        public ?array $destinations = null,
    ) {}

    /**
     * @param  array<string, mixed>  $fields  Stored `voice_answers.extracted` shape.
     */
    public static function fromStored(array $fields): self
    {
        /** @var list<array{code: string, years: int|null}>|null $countries */
        $countries = $fields['countries'] ?? null;

        return new self(
            occupation: $fields['occupation'] ?? null,
            specialization: $fields['specialization'] ?? null,
            yearsExperience: $fields['years_experience'] ?? null,
            countries: $countries === null ? null : array_map(fn (array $country) => new VoiceCountryData($country['code'], $country['years'] ?? null), $countries),
            skills: $fields['skills'] ?? null,
            passportStatus: $fields['passport_status'] ?? null,
            passportValidUntilYear: isset($fields['passport_valid_until_year']) ? (int) $fields['passport_valid_until_year'] : null,
            availability: $fields['availability'] ?? null,
            destinations: $fields['destinations'] ?? null,
        );
    }
}
