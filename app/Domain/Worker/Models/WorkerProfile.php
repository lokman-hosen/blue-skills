<?php

namespace App\Domain\Worker\Models;

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Consent;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Config\Models\Country;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\WorkerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * The worker-owned Global Skill Passport. Organizations only ever see projections of it.
 */
#[Fillable([
    'user_id', 'reference', 'headline', 'occupation_id', 'specialization_id', 'years_experience', 'current_country_code',
    'passport_status', 'passport_valid_until_year', 'availability', 'visibility', 'salary_expectation_minor',
    'salary_currency', 'salary_period', 'profile_completed_at', 'suspended_at'])]
#[UseFactory(WorkerProfileFactory::class)]
class WorkerProfile extends Model implements HasMedia
{
    /** @use HasFactory<WorkerProfileFactory> */
    use HasFactory, HasUlids, InteractsWithMedia, LogsStatusChanges;

    public const PHOTO_COLLECTION = 'photo';

    protected static function booted(): void
    {
        static::creating(function (WorkerProfile $profile): void {
            $profile->reference ??= self::newReference();
        });
    }

    /**
     * A random public Passport number such as BSK-0004182.
     */
    public static function newReference(): string
    {
        do {
            $reference = 'BSK-'.str_pad((string) random_int(0, 9_999_999), 7, '0', STR_PAD_LEFT);
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passport_status' => PassportStatus::class,
            'passport_valid_until_year' => 'integer',
            'availability' => Availability::class,
            'visibility' => ProfileVisibility::class,
            'years_experience' => 'integer',
            'salary_expectation_minor' => 'integer',
            'salary_period' => SalaryPeriod::class,
            'profile_completed_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Visibility changes are privacy decisions, so they go to the activity log.
     *
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['visibility'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTO_COLLECTION)
            ->singleFile()
            ->useDisk(config('blueskills.private_disk'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    /**
     * @return BelongsTo<Specialization, $this>
     */
    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function currentCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'current_country_code', 'code');
    }

    /**
     * @return HasMany<WorkerExperience, $this>
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(WorkerExperience::class)->orderByDesc('started_on');
    }

    /**
     * @return HasMany<WorkerSkill, $this>
     */
    public function workerSkills(): HasMany
    {
        return $this->hasMany(WorkerSkill::class);
    }

    /**
     * @return BelongsToMany<Skill, $this, WorkerSkill>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'worker_skills')
            ->using(WorkerSkill::class)
            ->withPivot(['id', 'source', 'confirmed_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<WorkerLanguage, $this>
     */
    public function languages(): HasMany
    {
        return $this->hasMany(WorkerLanguage::class);
    }

    /**
     * @return HasMany<WorkerDestinationPreference, $this>
     */
    public function destinationPreferences(): HasMany
    {
        return $this->hasMany(WorkerDestinationPreference::class)->orderBy('priority');
    }

    /**
     * @return HasMany<VoiceAnswer, $this>
     */
    public function voiceAnswers(): HasMany
    {
        return $this->hasMany(VoiceAnswer::class);
    }

    /**
     * @return HasMany<SkillShow, $this>
     */
    public function skillShows(): HasMany
    {
        return $this->hasMany(SkillShow::class);
    }

    /**
     * Invitations to apply from employers and agencies (Module 4).
     *
     * @return HasMany<JobInvitation, $this>
     */
    public function jobInvitations(): HasMany
    {
        return $this->hasMany(JobInvitation::class);
    }

    /**
     * Cohorts the worker was invited to or joined (Module 7).
     *
     * @return HasMany<CohortMember, $this>
     */
    public function cohortMemberships(): HasMany
    {
        return $this->hasMany(CohortMember::class);
    }

    /**
     * @return HasMany<EvidenceItem, $this>
     */
    public function evidenceItems(): HasMany
    {
        return $this->hasMany(EvidenceItem::class);
    }

    /**
     * @return HasMany<Consent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Found in talent search: visible to employers and agencies with an active discovery consent,
     * a trade, and an account that still exists (D-052, product rule 6).
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isDiscoverable(): bool
    {
        return self::query()->whereKey($this->id)->discoverable()->exists();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function discoverable(Builder $query): void
    {
        $query->where('visibility', ProfileVisibility::EmployersAndAgencies)
            // Suspended workers are hidden from search and pools (D-088).
            ->whereNull('suspended_at')
            ->whereNotNull('occupation_id')
            ->whereHas('user')
            ->whereHas('consents', fn (Builder $consents) => $consents->where('purpose', ConsentPurpose::Discovery)->whereNull('revoked_at'));
    }
}
