<?php

namespace App\Domain\Applications\Models;

use App\Domain\Applications\States\Application\ApplicationState;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Worker\Models\WorkerProfile;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\ModelStates\HasStates;

/**
 * One worker applying to one job (unique), with a dated profile snapshot (product rule 7).
 *
 * @property ApplicationState $status
 */
#[Fillable(['job_id', 'worker_profile_id', 'status', 'applied_at'])]
#[UseFactory(ApplicationFactory::class)]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationState::class,
            'applied_at' => 'datetime',
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
     * @return HasOne<ProfileSnapshot, $this>
     */
    public function snapshot(): HasOne
    {
        return $this->hasOne(ProfileSnapshot::class);
    }

    /**
     * @return HasMany<ApplicationConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(ApplicationConsent::class);
    }

    /**
     * @return HasOne<MobilityCase, $this>
     */
    public function mobilityCase(): HasOne
    {
        return $this->hasOne(MobilityCase::class);
    }
}
