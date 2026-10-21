<?php

namespace App\Domain\Applications\Data;

use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Data\EvidenceSummaryData;
use App\Domain\Worker\Data\SkillShowData;
use App\Domain\Worker\Data\WorkerExperienceData;
use App\Domain\Worker\Data\WorkerLanguageData;
use App\Domain\Worker\Data\WorkerSkillData;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Models\WorkerDestinationPreference;
use App\Domain\Worker\Models\WorkerExperience;
use App\Domain\Worker\Models\WorkerLanguage;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Models\WorkerSkill;
use Spatie\LaravelData\Data;

/**
 * The live professional projection of a discoverable worker for organizations (product rule 1).
 * Same shape as the application snapshot so both render the same way. Credentials show titles
 * and evidence states only; files are never part of it.
 */
class TalentProfileData extends Data
{
    /**
     * @param  list<WorkerSkillData>  $skills
     * @param  list<WorkerLanguageData>  $languages
     * @param  list<CountryOptionData>  $destinations
     * @param  list<WorkerExperienceData>  $experiences
     * @param  list<EvidenceSummaryData>  $credentials
     */
    public function __construct(
        public string $reference,
        public string $name,
        public ?string $headline,
        public ?CountryOptionData $currentCountry,
        public ?TaxonomyOptionData $occupation,
        public ?TaxonomyOptionData $specialization,
        public ?int $yearsExperience,
        public ?PassportStatus $passportStatus,
        public ?int $passportValidUntilYear,
        public ?Availability $availability,
        public array $skills,
        public array $languages,
        public array $destinations,
        public array $experiences,
        public array $credentials,
        public ?SkillShowData $skillShow,
    ) {}

    public static function fromProfile(WorkerProfile $profile): self
    {
        $profile->loadMissing([
            'user', 'currentCountry', 'occupation', 'specialization', 'experiences.country', 'workerSkills.skill',
            'languages.language', 'destinationPreferences.country', 'evidenceItems.documentable', 'skillShows',
        ]);
        $skillShow = TalentCardData::visibleSkillShow($profile);

        return new self(
            reference: $profile->reference,
            name: $profile->user->name,
            headline: $profile->headline,
            currentCountry: $profile->currentCountry === null ? null : CountryOptionData::fromCountry($profile->currentCountry),
            occupation: $profile->occupation === null ? null : TaxonomyOptionData::fromModel($profile->occupation),
            specialization: $profile->specialization === null ? null : TaxonomyOptionData::fromModel($profile->specialization),
            yearsExperience: $profile->years_experience,
            passportStatus: $profile->passport_status,
            passportValidUntilYear: $profile->passport_valid_until_year,
            availability: $profile->availability,
            skills: $profile->workerSkills
                ->map(fn (WorkerSkill $workerSkill) => new WorkerSkillData($workerSkill->skill_id, $workerSkill->skill->slug, $workerSkill->skill->translatedName(), $workerSkill->source))
                ->values()
                ->all(),
            languages: $profile->languages->map(fn (WorkerLanguage $language) => WorkerLanguageData::fromLanguage($language))->values()->all(),
            destinations: $profile->destinationPreferences
                ->map(fn (WorkerDestinationPreference $preference) => $preference->country === null ? null : CountryOptionData::fromCountry($preference->country))
                ->filter()
                ->values()
                ->all(),
            experiences: $profile->experiences->map(fn (WorkerExperience $experience) => WorkerExperienceData::fromExperience($experience))->values()->all(),
            credentials: $profile->evidenceItems
                ->filter(fn (EvidenceItem $item) => EvidenceSummaryData::shownToOrganizations($item))
                ->map(fn (EvidenceItem $item) => EvidenceSummaryData::fromEvidence($item))
                ->values()
                ->all(),
            skillShow: $skillShow !== null ? SkillShowData::fromSkillShow($skillShow) : null,
        );
    }
}
