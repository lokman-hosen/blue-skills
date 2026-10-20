<?php

namespace App\Domain\Verification\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\States\Evidence\EvidenceState;
use App\Domain\Worker\Models\WorkerProfile;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\EvidenceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\ModelStates\HasStates;

/**
 * One independently verified claim (product rule 9). Never aggregate into a blanket
 * "verified skills" badge.
 *
 * @property EvidenceState $status
 */
#[Fillable(['worker_profile_id', 'type', 'documentable_type', 'documentable_id', 'status', 'verified_by', 'verified_at', 'reason_code'])]
#[UseFactory(EvidenceItemFactory::class)]
class EvidenceItem extends Model
{
    /** @use HasFactory<EvidenceItemFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EvidenceType::class,
            'status' => EvidenceState::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return MorphMany<VerificationRequest, $this>
     */
    public function verificationRequests(): MorphMany
    {
        return $this->morphMany(VerificationRequest::class, 'subject');
    }
}
