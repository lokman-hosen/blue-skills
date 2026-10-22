<?php

namespace App\Domain\Organization\Models;

use App\Domain\Applications\Models\Shortlist;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Billing\States\Subscription\Active as SubscriptionActive;
use App\Domain\Config\Models\Country;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Policies\OrganizationPolicy;
use App\Domain\Organization\States\Verification\OrganizationVerificationState;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Verification\Models\VerificationRequest;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

/**
 * @property OrganizationType $type
 * @property OrganizationVerificationState $verification_status
 */
#[Fillable([
    'type', 'legal_name', 'display_name', 'country_code', 'city', 'registration_number',
    'licence_number', 'licence_expires_on', 'licence_countries', 'verification_status', 'verified_at', 'data_region',
    'pilot_invite_id',
])]
#[UseFactory(OrganizationFactory::class)]
#[UsePolicy(OrganizationPolicy::class)]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'verification_status' => OrganizationVerificationState::class,
            'verified_at' => 'datetime',
            'licence_expires_on' => 'date',
            'licence_countries' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['verification_status'];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * @return HasMany<OrganizationMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    /**
     * @return BelongsToMany<User, $this, OrganizationMember>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_members')
            ->using(OrganizationMember::class)
            ->withPivot(['id', 'title', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<OrganizationInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    /**
     * Jobs this organization posted (as employer or agency).
     *
     * @return HasMany<JobPosting, $this>
     */
    public function postedJobs(): HasMany
    {
        return $this->hasMany(JobPosting::class, 'organization_id');
    }

    /**
     * Institutes: the courses they run (Module 7).
     *
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * @return HasMany<Cohort, $this>
     */
    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class);
    }

    /**
     * @return HasMany<TalentPool, $this>
     */
    public function talentPools(): HasMany
    {
        return $this->hasMany(TalentPool::class);
    }

    /**
     * @return HasMany<RecruitmentProject, $this>
     */
    public function recruitmentProjects(): HasMany
    {
        return $this->hasMany(RecruitmentProject::class);
    }

    /**
     * @return HasMany<OrganizationSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }

    /**
     * @return HasOne<OrganizationSubscription, $this>
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(OrganizationSubscription::class)->where('status', SubscriptionActive::$name)->latestOfMany('starts_on');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<CreditLedgerEntry, $this>
     */
    public function creditEntries(): HasMany
    {
        return $this->hasMany(CreditLedgerEntry::class);
    }

    public function creditBalance(): int
    {
        return (int) $this->creditEntries()->sum('delta');
    }

    /**
     * @return HasMany<Shortlist, $this>
     */
    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class);
    }

    /**
     * Jobs where this organization is the actual employer.
     *
     * @return HasMany<JobPosting, $this>
     */
    public function employerJobs(): HasMany
    {
        return $this->hasMany(JobPosting::class, 'employer_organization_id');
    }

    /**
     * @return HasMany<JobMandate, $this>
     */
    public function agencyMandates(): HasMany
    {
        return $this->hasMany(JobMandate::class, 'agency_organization_id');
    }

    /**
     * @return HasMany<JobMandate, $this>
     */
    public function employerMandates(): HasMany
    {
        return $this->hasMany(JobMandate::class, 'employer_organization_id');
    }

    /**
     * The pilot code it registered with (D-108).
     *
     * @return BelongsTo<PilotInvite, $this>
     */
    public function pilotInvite(): BelongsTo
    {
        return $this->belongsTo(PilotInvite::class);
    }

    /**
     * @return MorphMany<VerificationRequest, $this>
     */
    public function verificationRequests(): MorphMany
    {
        return $this->morphMany(VerificationRequest::class, 'subject');
    }

    /**
     * The newest staff review of this organization that is still waiting for a decision.
     */
    public function openVerificationRequest(): ?VerificationRequest
    {
        return $this->verificationRequests()
            ->whereIn('status', ['open', 'in_review', 'needs_info'])
            ->latest()
            ->first();
    }

    public function isVerified(): bool
    {
        return $this->verification_status->equals(Verified::class);
    }

    /**
     * A verified agency whose licence has not expired and, when a destination is given, covers
     * it (product rule 10).
     */
    public function hasValidLicence(?string $destinationCountryCode = null): bool
    {
        if ($this->type !== OrganizationType::Agency || ! $this->isVerified()) {
            return false;
        }

        if ($this->licence_expires_on === null || $this->licence_expires_on->isPast()) {
            return false;
        }

        return $destinationCountryCode === null
            || in_array($destinationCountryCode, $this->licence_countries ?? [], true);
    }
}
