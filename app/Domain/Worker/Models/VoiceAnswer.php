<?php

namespace App\Domain\Worker\Models;

use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use Database\Factories\VoiceAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One Voice-to-CV answer. Extracted fields are never saved to the profile until the worker
 * confirms them (product rule 2).
 */
#[Fillable(['worker_profile_id', 'question_key', 'locale', 'transcript', 'extracted', 'status', 'typed', 'error_code', 'confirmed_at'])]
#[UseFactory(VoiceAnswerFactory::class)]
class VoiceAnswer extends Model implements HasMedia
{
    /** @use HasFactory<VoiceAnswerFactory> */
    use HasFactory, HasUlids, InteractsWithMedia;

    public const AUDIO_COLLECTION = 'audio';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_key' => VoiceQuestion::class,
            'extracted' => 'array',
            'status' => VoiceAnswerStatus::class,
            'typed' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::AUDIO_COLLECTION)
            ->singleFile()
            ->useDisk(config('blueskills.private_disk'));
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }
}
