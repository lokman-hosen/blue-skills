<?php

namespace App\Domain\Institute\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\AssessmentResult;
use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Models\WorkerProfile;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\TradeAssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * A trade assessment the institute ran (pass or not, with an optional score). It reaches the
 * worker's Skill Passport as its own "issuer verified" evidence item (product rules 9 and 11).
 *
 * @property Carbon $assessed_on
 * @property Carbon|null $withdrawn_at
 */
#[Fillable([
    'organization_id', 'cohort_member_id', 'worker_profile_id', 'occupation_id', 'title', 'result', 'score', 'assessed_on', 'note', 'status',
    'assessed_by', 'withdrawn_by', 'withdrawn_at', 'withdrawn_reason',
])]
#[UseFactory(TradeAssessmentFactory::class)]
class TradeAssessment extends Model
{
    /** @use HasFactory<TradeAssessmentFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assessed_on' => 'date',
            'result' => AssessmentResult::class,
            'score' => 'integer',
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
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
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
