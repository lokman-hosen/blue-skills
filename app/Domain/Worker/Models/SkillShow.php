<?php

namespace App\Domain\Worker\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\SkillShowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A 30-second worker-provided demonstration. Moderated before publishing and never a
 * certification (product rule 3).
 */
#[Fillable(['worker_profile_id', 'duration_ms', 'visibility', 'moderation_status', 'reason_code', 'moderated_by', 'moderated_at'])]
#[UseFactory(SkillShowFactory::class)]
class SkillShow extends Model implements HasMedia
{
    /** @use HasFactory<SkillShowFactory> */
    use HasFactory, HasUlids, InteractsWithMedia, LogsStatusChanges;

    /** The upload as received; replaced by the converted MP4 once checked. */
    public const UPLOAD_COLLECTION = 'upload';

    public const VIDEO_COLLECTION = 'video';

    public const POSTER_COLLECTION = 'poster';

    public const MAX_DURATION_MS = 30_000;

    /** Container and codec rounding: a 30-second recording can measure a few frames longer. */
    public const DURATION_TOLERANCE_MS = 500;

    public const MAX_UPLOAD_KB = 51_200;

    /** @var list<string> */
    public const ACCEPTED_MIME_TYPES = ['video/mp4', 'video/webm', 'video/quicktime', 'video/3gpp'];

    public const DISCLAIMER = 'Worker-provided demonstration, not certification';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
            'visibility' => ProfileVisibility::class,
            'moderation_status' => ModerationStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['moderation_status', 'visibility'];
    }

    public function registerMediaCollections(): void
    {
        foreach ([self::UPLOAD_COLLECTION, self::VIDEO_COLLECTION, self::POSTER_COLLECTION] as $collection) {
            $this->addMediaCollection($collection)
                ->singleFile()
                ->useDisk(config('blueskills.private_disk'));
        }
    }

    public function isPublished(): bool
    {
        return $this->moderation_status === ModerationStatus::Approved;
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
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_show_skill');
    }
}
