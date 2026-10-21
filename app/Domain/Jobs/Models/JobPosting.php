<?php

namespace App\Domain\Jobs\Models;

use App\Domain\Applications\Models\Application;
use App\Domain\Config\Models\Country;
use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\JobState;
use App\Domain\Jobs\States\Job\MoreInformationRequested;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Rejected;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Verification\Models\VerificationRequest;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

/**
 * The structured job object behind every Job Truth Card (spec §5, product rule 4).
 * Stored in the `jobs` table; named JobPosting to avoid confusion with queued jobs.
 *
 * @property JobState $status
 * @property Collection<int, JobCost> $costs
 */
#[Table('jobs')]
#[RouteKey('public_code')]
#[Fillable([
    'organization_id', 'employer_organization_id', 'agency_organization_id', 'job_mandate_id', 'public_code',
    'title', 'occupation_id', 'min_experience_years', 'language_requirements', 'destination_country_code',
    'destination_city', 'headcount', 'salary_amount_minor', 'salary_currency', 'salary_period', 'hours_per_day',
    'days_per_week', 'overtime_terms', 'contract_months',
    'accommodation', 'accommodation_details', 'food', 'food_details', 'transport', 'transport_details',
    'medical', 'medical_details', 'airfare', 'airfare_details', 'other_benefits',
    'permit_payer', 'visa_payer', 'joining_from', 'joining_to', 'deadline', 'status', 'published_at',
    'submitted_at', 'material_change_at',
])]
#[UseFactory(JobPostingFactory::class)]
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges, SoftDeletes;

    public const BENEFITS = ['accommodation', 'food', 'transport', 'medical', 'airfare'];

    /**
     * Changing any of these on a live job sends it back to review (product rule 5). Worker
     * costs are compared separately.
     */
    public const MATERIAL_FIELDS = [
        'employer_organization_id', 'destination_country_code', 'destination_city',
        'salary_amount_minor', 'salary_currency', 'salary_period', 'hours_per_day', 'days_per_week',
        'overtime_terms', 'contract_months',
        'accommodation', 'accommodation_details', 'food', 'food_details', 'transport', 'transport_details',
        'medical', 'medical_details', 'airfare', 'airfare_details', 'other_benefits',
        'permit_payer', 'visa_payer',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'language_requirements' => 'array',
            'headcount' => 'integer',
            'min_experience_years' => 'integer',
            'salary_amount_minor' => 'integer',
            'salary_period' => SalaryPeriod::class,
            'hours_per_day' => 'decimal:2',
            'days_per_week' => 'integer',
            'contract_months' => 'integer',
            'accommodation' => BenefitProvision::class,
            'food' => BenefitProvision::class,
            'transport' => BenefitProvision::class,
            'medical' => BenefitProvision::class,
            'airfare' => BenefitProvision::class,
            'permit_payer' => CostPayer::class,
            'visa_payer' => CostPayer::class,
            'joining_from' => 'date',
            'joining_to' => 'date',
            'deadline' => 'date',
            'status' => JobState::class,
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'material_change_at' => 'datetime',
        ];
    }

    /**
     * The organization that posted the job (employer or agency).
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'employer_organization_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'agency_organization_id');
    }

    /**
     * @return BelongsTo<JobMandate, $this>
     */
    public function mandate(): BelongsTo
    {
        return $this->belongsTo(JobMandate::class, 'job_mandate_id');
    }

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function destinationCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'destination_country_code', 'code');
    }

    /**
     * @return HasMany<JobCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(JobCost::class, 'job_id');
    }

    /**
     * @return HasMany<JobSkillRequirement, $this>
     */
    public function skillRequirements(): HasMany
    {
        return $this->hasMany(JobSkillRequirement::class, 'job_id');
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill_requirements', 'job_id')
            ->withPivot(['id', 'required'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_id');
    }

    /**
     * @return MorphMany<VerificationRequest, $this>
     */
    public function verificationRequests(): MorphMany
    {
        return $this->morphMany(VerificationRequest::class, 'subject');
    }

    /**
     * Drafts, jobs sent back by a reviewer and live jobs can be edited. Jobs waiting for or
     * under review, suspended, closed or archived cannot.
     */
    public function isEditable(): bool
    {
        return $this->status->equals(Draft::class, MoreInformationRequested::class, Rejected::class, Published::class, Paused::class);
    }

    /**
     * Published and the deadline (if any) has not passed.
     */
    public function acceptsApplications(): bool
    {
        return $this->status->equals(Published::class)
            && ($this->deadline === null || ! $this->deadline->isPast() || $this->deadline->isToday());
    }

    public function isLive(): bool
    {
        return $this->status->equals(Published::class, Paused::class);
    }

    /**
     * The newest staff review of this job, open or decided.
     */
    public function latestReview(): ?VerificationRequest
    {
        return $this->verificationRequests()->latest()->first();
    }

    /**
     * Every cost the worker pays, itemised (product rule 4).
     *
     * @return Collection<int, JobCost>
     */
    public function workerCosts(): Collection
    {
        return $this->costs->where('payer', CostPayer::Worker)->values();
    }
}
