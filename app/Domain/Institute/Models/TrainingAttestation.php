<?php

namespace App\Domain\Institute\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Models\WorkerProfile;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\TrainingAttestationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * The institute's statement that a graduate completed a course. It reaches the worker's Skill
 * Passport as its own "issuer verified" evidence item and never edits their career history
 * (product rules 9 and 11).
 *
 * @property Carbon $completed_on
 * @property Carbon|null $withdrawn_at
 */
#[Fillable([
    'organization_id', 'cohort_member_id', 'worker_profile_id', 'course_title', 'completed_on', 'note', 'status',
    'issued_by', 'withdrawn_by', 'withdrawn_at', 'withdrawn_reason',
])]
#[UseFactory(TrainingAttestationFactory::class)]
class TrainingAttestation extends Model
{
    /** @use HasFactory<TrainingAttestationFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_on' => 'date',
            'status' => IssuedRecordStatus::class,
            'withdrawn_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<CohortMember, $this>
     */
    public function cohortMember(): BelongsTo
    {
        return $this->belongsTo(CohortMember::class);
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return MorphOne<EvidenceItem, $this>
     */
    public function evidence(): MorphOne
    {
        return $this->morphOne(EvidenceItem::class, 'documentable');
    }

    public function isIssued(): bool
    {
        return $this->status === IssuedRecordStatus::Issued;
    }
}
