<?php

namespace App\Domain\Jobs\Models;

use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Models\VerificationRequest;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\JobMandateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * An employer's authorization for an agency to recruit (product rule 10).
 *
 * @property MandateStatus $status
 */
#[Fillable(['employer_organization_id', 'agency_organization_id', 'reference', 'headcount', 'valid_from', 'valid_until', 'status'])]
#[UseFactory(JobMandateFactory::class)]
class JobMandate extends Model
{
    /** @use HasFactory<JobMandateFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headcount' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'status' => MandateStatus::class,
        ];
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
     * @return HasMany<JobPosting, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(JobPosting::class, 'job_mandate_id');
    }

    /**
     * @return MorphMany<VerificationRequest, $this>
     */
    public function verificationRequests(): MorphMany
    {
        return $this->morphMany(VerificationRequest::class, 'subject');
    }

    public function isVerified(): bool
    {
        return $this->status === MandateStatus::Verified;
    }
}
