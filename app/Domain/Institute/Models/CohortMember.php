<?php

namespace App\Domain\Institute\Models;

use App\Domain\Institute\Enums\CohortMemberSource;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Worker\Models\WorkerProfile;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\CohortMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A graduate's link to a cohort. The institute sees the worker's profile only while the link is
 * accepted, and their applications only while they share progress (product rule 11, D-082).
 *
 * @property Carbon|null $responded_at
 * @property Carbon|null $left_at
 */
#[Fillable(['cohort_id', 'worker_profile_id', 'roster_name', 'roster_phone', 'status', 'source', 'shares_progress', 'responded_at', 'left_at'])]
#[UseFactory(CohortMemberFactory::class)]
class CohortMember extends Model
{
    /** @use HasFactory<CohortMemberFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CohortMemberStatus::class,
            'source' => CohortMemberSource::class,
            'shares_progress' => 'boolean',
            'responded_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status', 'shares_progress'];
    }

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return HasMany<TrainingAttestation, $this>
     */
    public function attestations(): HasMany
    {
        return $this->hasMany(TrainingAttestation::class);
    }

    /**
     * @return HasMany<TradeAssessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(TradeAssessment::class);
    }

    public function isAccepted(): bool
    {
        return $this->status === CohortMemberStatus::Accepted && $this->worker_profile_id !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function accepted(Builder $query): void
    {
        $query->where('status', CohortMemberStatus::Accepted)->whereNotNull('worker_profile_id');
    }
}
