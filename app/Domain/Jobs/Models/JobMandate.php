<?php

namespace App\Domain\Jobs\Models;

use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\States\Job\Archived;
use App\Domain\Jobs\States\Job\Closed;
use App\Domain\Jobs\States\Job\Rejected;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Models\VerificationRequest;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\JobMandateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
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
#[Fillable([
    'employer_organization_id', 'agency_organization_id', 'reference', 'headcount', 'valid_from', 'valid_until', 'status',
    'occupation_ids', 'destination_country_code', 'requested_by', 'employer_responded_by', 'employer_responded_at', 'employer_note',
])]
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
            'occupation_ids' => 'array',
            'employer_responded_at' => 'datetime',
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

    /**
     * Can back a job today: verified by staff, within its dates, and the agency's licence is
     * valid for the destination (product rule 10).
     */
    public function isUsable(): bool
    {
        $today = now()->startOfDay();

        return $this->isVerified()
            && ($this->valid_from === null || ! $this->valid_from->isAfter($today))
            && ($this->valid_until === null || ! $this->valid_until->isBefore($today))
            && $this->agency->hasValidLicence($this->destination_country_code);
    }

    /**
     * Whether a job's trade and destination fall inside the mandate's scope.
     */
    public function covers(?string $occupationId, ?string $destinationCountryCode): bool
    {
        $occupations = $this->occupation_ids ?? [];

        return ($occupations === [] || in_array($occupationId, $occupations, true))
            && ($this->destination_country_code === null || $this->destination_country_code === $destinationCountryCode);
    }

    /**
     * Places still open under this mandate: its headcount minus what the agency's other open
     * jobs already recruit for.
     */
    public function headcountLeft(?JobPosting $except = null): int
    {
        $used = $this->jobs()
            ->whereNotState('status', [Closed::class, Archived::class, Rejected::class])
            ->when($except?->exists, fn ($query) => $query->whereKeyNot($except?->getKey()))
            ->sum('headcount');

        return max(0, $this->headcount - (int) $used);
    }

    /**
     * The agency's mandates that can back a new job today (product rule 10).
     *
     * @return Collection<int, self>
     */
    public static function usableFor(Organization $agency): Collection
    {
        return self::query()
            ->where('agency_organization_id', $agency->id)
            ->where('status', MandateStatus::Verified)
            ->with(['agency', 'employer'])
            ->orderBy('valid_until')
            ->get()
            ->filter(fn (self $mandate) => $mandate->isUsable())
            ->values();
    }
}
