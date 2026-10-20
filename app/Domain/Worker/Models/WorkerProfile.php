<?php

namespace App\Domain\Worker\Models;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Consent;
use App\Domain\Config\Models\Country;
use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use Database\Factories\WorkerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The worker-owned Global Skill Passport. Organizations only ever see projections of it.
 */
#[Fillable([
    'user_id', 'headline', 'occupation_id', 'specialization_id', 'years_experience', 'current_country_code',
    'passport_status', 'availability', 'visibility', 'salary_expectation_minor', 'salary_currency',
    'profile_completed_at',
])]
#[UseFactory(WorkerProfileFactory::class)]
class WorkerProfile extends Model
{
    /** @use HasFactory<WorkerProfileFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passport_status' => PassportStatus::class,
            'availability' => Availability::class,
            'visibility' => ProfileVisibility::class,
            'years_experience' => 'integer',
            'salary_expectation_minor' => 'integer',
            'profile_completed_at' => 'datetime',
        ];
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
}
