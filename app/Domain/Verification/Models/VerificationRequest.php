<?php

namespace App\Domain\Verification\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Verification\Enums\VerificationDecision;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\States\VerificationRequest\VerificationRequestState;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\VerificationRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\ModelStates\HasStates;

/**
 * Staff verification queue item for organizations, licences, mandates, jobs, worker
 * evidence and SkillShows (spec §10).
 *
 * @property VerificationRequestState $status
 */
#[Fillable([
    'subject_type', 'subject_id', 'type', 'status', 'assigned_to', 'decided_by', 'decision',
    'reason_code', 'notes', 'decided_at',
])]
#[UseFactory(VerificationRequestFactory::class)]
class VerificationRequest extends Model implements HasMedia
{
    /** @use HasFactory<VerificationRequestFactory> */
    use HasFactory, HasStates, HasUlids, InteractsWithMedia, LogsStatusChanges;

    public const EVIDENCE_COLLECTION = 'evidence';

    public const EVIDENCE_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    public const EVIDENCE_MAX_KB = 10_240;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VerificationType::class,
            'status' => VerificationRequestState::class,
            'decision' => VerificationDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::EVIDENCE_COLLECTION)
            ->useDisk(config('blueskills.private_disk'))
            ->acceptsMimeTypes(self::EVIDENCE_MIME_TYPES);
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status', 'decision', 'reason_code', 'assigned_to'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
