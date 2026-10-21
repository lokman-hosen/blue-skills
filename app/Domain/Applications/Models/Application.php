<?php

namespace App\Domain\Applications\Models;

use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\ApplicationState;
use App\Domain\Applications\States\Application\Declined;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Applications\States\Application\Withdrawn;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Worker\Models\SkillShow;
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
#[Fillable([
    'job_id', 'worker_profile_id', 'status', 'applied_at', 'assigned_user_id', 'skill_show_id',
    'stage_changed_at', 'job_change_seen_at',
])]
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
            'stage_changed_at' => 'datetime',
            'job_change_seen_at' => 'datetime',
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

    /**
     * @return HasOne<Conversation, $this>
     */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /**
     * The recruiter handling the application, shown to the worker as "Handled by".
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * The SkillShow shared with this application, if the worker consented.
     *
     * @return BelongsTo<SkillShow, $this>
     */
    public function skillShow(): BelongsTo
    {
        return $this->belongsTo(SkillShow::class);
    }

    /**
     * @return HasMany<ApplicationStageEvent, $this>
     */
    public function stageEvents(): HasMany
    {
        return $this->hasMany(ApplicationStageEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<Interview, $this>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->orderBy('scheduled_at');
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class)->latest()->orderByDesc('id');
    }

    /**
     * The offer waiting for the worker's answer, if any.
     */
    public function openOffer(): ?Offer
    {
        return $this->offers()->where('status', OfferStatus::Sent)->first();
    }

    /**
     * Rejected, withdrawn, accepted and declined applications no longer move.
     */
    public function isClosed(): bool
    {
        return $this->status->equals(Rejected::class, Withdrawn::class, Accepted::class, Declined::class);
    }

    /**
     * The job changed materially after the worker applied and they have not seen it yet.
     */
    public function hasUnseenJobChange(): bool
    {
        $changedAt = $this->job->material_change_at;

        return $changedAt !== null
            && $changedAt->greaterThan($this->applied_at)
            && ($this->job_change_seen_at === null || $changedAt->greaterThan($this->job_change_seen_at));
    }
}
