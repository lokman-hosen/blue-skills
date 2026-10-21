<?php

namespace App\Domain\Applications\Models;

use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Worker\Models\WorkerProfile;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\JobInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An organization inviting a discoverable worker to apply to one of its open jobs. A fixed,
 * structured message: free conversation starts only once the worker applies.
 *
 * @property Carbon|null $responded_at
 */
#[Fillable(['job_id', 'worker_profile_id', 'organization_id', 'invited_by', 'note', 'status', 'responded_at'])]
#[UseFactory(JobInvitationFactory::class)]
class JobInvitation extends Model
{
    /** @use HasFactory<JobInvitationFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /** Invitations an organization may send per day. */
    public const DAILY_LIMIT = 50;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
