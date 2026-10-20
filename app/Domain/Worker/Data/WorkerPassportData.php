<?php

namespace App\Domain\Worker\Data;

use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerDestinationPreference;
use App\Domain\Worker\Models\WorkerExperience;
use App\Domain\Worker\Models\WorkerLanguage;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Models\WorkerSkill;
use App\Domain\Worker\Support\MediaUrls;
use App\Support\Data\MoneyData;
use Spatie\LaravelData\Data;

/**
 * The worker's own, complete view of their Skill Passport. Organizations never receive this
 * object; they get role-scoped projections (product rule 1).
 */
class WorkerPassportData extends Data
{
    /** ISO 3166 alpha-3 for the machine-readable line on the Passport cover. */
    private const ALPHA3 = [
        'BD' => 'BGD', 'NP' => 'NPL', 'IN' => 'IND', 'PK' => 'PAK', 'LK' => 'LKA', 'AE' => 'ARE',
        'SA' => 'SAU', 'QA' => 'QAT', 'OM' => 'OMN', 'PL' => 'POL', 'JP' => 'JPN', 'CZ' => 'CZE',
    ];

    /**
     * @param  list<WorkerExperienceData>  $experiences
     * @param  list<WorkerSkillData>  $skills
     * @param  list<WorkerLanguageData>  $languages
     * @param  list<CountryOptionData>  $destinations
     * @param  list<EvidenceSummaryData>  $evidence
     */
    public function __construct(
        public string $reference,
        public string $name,
        public ?string $headline,
        public ?string $photoUrl,
        public ?CountryOptionData $currentCountry,
        public string $nationality,
        public ?TaxonomyOptionData $occupation,
        public ?TaxonomyOptionData $specialization,
        public ?int $yearsExperience,
        public ?PassportStatus $passportStatus,
        public ?int $passportValidUntilYear,
        public ?Availability $availability,
        public ?MoneyData $salaryExpectation,
        public ?SalaryPeriod $salaryPeriod,
        public ProfileVisibility $visibility,
        public array $experiences,
        public array $skills,
        public array $languages,
        public array $destinations,
        public ?SkillShowData $skillShow,
        public bool $phoneVerified,
        public array $evidence,
        public ProfileCompletionData $completion,
    ) {}

    public static function fromProfile(WorkerProfile $profile): self
    {
        $profile->loadMissing([
            'user', 'currentCountry', 'occupation', 'specialization', 'experiences.country',
            'workerSkills.skill', 'languages.language', 'destinationPreferences.country', 'evidenceItems.documentable',
        ]);

        $skillShow = $profile->skillShows()
            ->where('moderation_status', '!=', ModerationStatus::Failed)
            ->latest()
            ->first();

        $destinationCountries = $profile->destinationPreferences
            ->map(fn (WorkerDestinationPreference $preference) => $preference->country === null ? null : CountryOptionData::fromCountry($preference->country))
            ->filter()
            ->values()
            ->all();

        return new self(
            reference: $profile->reference,
            name: $profile->user->name,
            headline: $profile->headline,
            photoUrl: MediaUrls::signed($profile->getFirstMedia(WorkerProfile::PHOTO_COLLECTION)),
            currentCountry: $profile->currentCountry === null ? null : CountryOptionData::fromCountry($profile->currentCountry),
            nationality: self::ALPHA3[$profile->current_country_code] ?? (string) $profile->current_country_code,
            occupation: $profile->occupation === null ? null : TaxonomyOptionData::fromModel($profile->occupation),
            specialization: $profile->specialization === null ? null : TaxonomyOptionData::fromModel($profile->specialization),
            yearsExperience: $profile->years_experience,
            passportStatus: $profile->passport_status,
            passportValidUntilYear: $profile->passport_valid_until_year,
            availability: $profile->availability,
            salaryExpectation: $profile->salary_expectation_minor !== null && $profile->salary_currency !== null
                ? new MoneyData($profile->salary_expectation_minor, $profile->salary_currency)
                : null,
            salaryPeriod: $profile->salary_period,
            visibility: $profile->visibility,
            experiences: $profile->experiences->map(fn (WorkerExperience $experience) => WorkerExperienceData::fromExperience($experience))->values()->all(),
            skills: $profile->workerSkills
                ->map(fn (WorkerSkill $workerSkill) => new WorkerSkillData($workerSkill->skill_id, $workerSkill->skill->slug, $workerSkill->skill->translatedName(), $workerSkill->source))
                ->values()
                ->all(),
            languages: $profile->languages->map(fn (WorkerLanguage $language) => WorkerLanguageData::fromLanguage($language))->values()->all(),
            destinations: $destinationCountries,
            skillShow: $skillShow instanceof SkillShow ? SkillShowData::fromSkillShow($skillShow) : null,
            phoneVerified: $profile->user->phone_verified_at !== null,
            evidence: $profile->evidenceItems->map(fn (EvidenceItem $item) => EvidenceSummaryData::fromEvidence($item))->values()->all(),
            completion: ProfileCompletionData::fromProfile($profile),
        );
    }
}
