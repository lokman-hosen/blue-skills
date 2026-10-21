<?php

namespace App\Domain\Organization\Models;

use App\Domain\Applications\Models\Shortlist;
use App\Domain\Config\Models\Country;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

/**
 * @property OrganizationType $type
 * @property OrganizationVerificationState $verification_status
 */
#[Fillable([
    'type', 'legal_name', 'display_name', 'country_code', 'city', 'registration_number',
    'licence_number', 'licence_expires_on', 'verification_status', 'verified_at', 'data_region',
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
}
