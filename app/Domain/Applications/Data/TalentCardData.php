<?php

namespace App\Domain\Applications\Data;

use App\Domain\Jobs\Data\MatchSummaryData;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerExperience;
use App\Domain\Worker\Models\WorkerLanguage;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Models\WorkerSkill;
use App\Domain\Worker\Support\MediaUrls;
use Spatie\LaravelData\Data;

/**
 * One talent search result (deck slide 17): practical skills first, evidence states as they are,
 * never a phone number, document or address (product rule 6).
 */
class TalentCardData extends Data
{
    /**
     * @param  list<string>  $countriesWorked
     * @param  list<string>  $skills
     * @param  list<string>  $languages
     */
    public function __construct(
        public string $reference,
        public string $name,
        public ?string $headline,
        public ?string $occupation,
        public ?int $yearsExperience,
        public ?string $currentCountry,
        public array $countriesWorked,
        public array $skills,
        public array $languages,
        public ?PassportStatus $passportStatus,
        public ?Availability $availability,
        public int $verifiedEvidence,
        public ?string $skillShowPosterUrl,
        public ?MatchSummaryData $match,
    ) {}

    public static function fromProfile(WorkerProfile $profile, ?MatchSummaryData $match = null): self
    {
        $skillShow = self::visibleSkillShow($profile);

        return new self(
            reference: $profile->reference,
            name: $profile->user->name,
            headline: $profile->headline,
            occupation: $profile->occupation?->translatedName(),
            yearsExperience: $profile->years_experience,
            currentCountry: $profile->currentCountry?->name,
            countriesWorked: $profile->experiences
                ->map(fn (WorkerExperience $experience) => $experience->country->name ?? $experience->country_code)
                ->unique()
                ->values()
                ->all(),
            skills: $profile->workerSkills->map(fn (WorkerSkill $workerSkill) => $workerSkill->skill->translatedName())->values()->all(),
            languages: $profile->languages->map(fn (WorkerLanguage $language) => $language->language->native_name ?? $language->language_code)->values()->all(),
            passportStatus: $profile->passport_status,
            availability: $profile->availability,
            verifiedEvidence: $profile->evidenceItems->filter(fn (EvidenceItem $item) => $item->status->getValue() === 'issuer_verified')->count(),
            skillShowPosterUrl: $skillShow !== null ? MediaUrls::signed($skillShow->getFirstMedia(SkillShow::POSTER_COLLECTION)) : null,
            match: $match,
        );
    }

    /**
     * The approved SkillShow the worker made visible to employers and agencies.
     */
    public static function visibleSkillShow(WorkerProfile $profile): ?SkillShow
    {
        return $profile->skillShows->first(fn (SkillShow $skillShow) => $skillShow->isPublished() && $skillShow->visibility === ProfileVisibility::EmployersAndAgencies);
    }
}
