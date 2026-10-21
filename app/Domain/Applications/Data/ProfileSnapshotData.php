<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Jobs\Data\MatchSummaryData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Support\JobMatch;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Actions\AddWorkerCredential;
use App\Domain\Worker\Data\EvidenceSummaryData;
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
use Illuminate\Support\Facades\App;
use Spatie\LaravelData\Data;

/**
 * The dated copy of the Skill Passport stored with an application (product rule 7). It holds
 * only what the worker chose to share; phone numbers and document files are never copied in,
 * they are read live while the consent is active. Names are captured in English so every
 * reviewer reads the same record.
 */
class ProfileSnapshotData extends Data
{
    /**
     * @param  list<WorkerSkillData>  $skills
     * @param  list<WorkerLanguageData>  $languages
     * @param  list<CountryOptionData>  $destinations
     * @param  list<WorkerExperienceData>  $experiences  Empty unless work experience was shared.
     * @param  list<EvidenceSummaryData>  $credentials  Empty unless training certificates were shared.
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
        public MatchSummaryData $match,
        public string $takenAt,
    ) {}

    /**
     * @param  list<ApplicationConsentItem>  $shared
     */
    public static function capture(WorkerProfile $profile, JobPosting $job, array $shared): self
    {
        $locale = App::getLocale();
        App::setLocale((string) config('app.fallback_locale', 'en'));

        try {
            return self::build($profile, $job, $shared);
        } finally {
            App::setLocale($locale);
        }
    }

    /**
     * @param  list<ApplicationConsentItem>  $shared
     */
    private static function build(WorkerProfile $profile, JobPosting $job, array $shared): self
    {
        $profile->loadMissing([
            'user', 'currentCountry', 'occupation', 'specialization', 'experiences.country', 'workerSkills.skill',
            'languages.language', 'destinationPreferences.country', 'evidenceItems.documentable',
        ]);

        $experiences = in_array(ApplicationConsentItem::WorkExperience, $shared, true)
            ? $profile->experiences->map(fn (WorkerExperience $experience) => WorkerExperienceData::fromExperience($experience))->values()->all()
            : [];

        $credentials = in_array(ApplicationConsentItem::TrainingCertificates, $shared, true)
            ? $profile->evidenceItems
                ->filter(fn (EvidenceItem $item) => in_array($item->type, AddWorkerCredential::TYPES, true))
                ->map(fn (EvidenceItem $item) => EvidenceSummaryData::fromEvidence($item))
                ->values()
                ->all()
            : [];

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
            experiences: $experiences,
            credentials: $credentials,
            match: JobMatch::explain($profile, $job),
            takenAt: now()->toIso8601String(),
        );
    }
}
