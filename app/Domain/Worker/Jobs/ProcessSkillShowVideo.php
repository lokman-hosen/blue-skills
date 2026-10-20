<?php

namespace App\Domain\Worker\Jobs;

use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Media\SkillShowVideoProcessor;
use App\Domain\Worker\Models\SkillShow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Validates a SkillShow upload on the server (≤ 30 seconds, a readable video), converts it
 * and hands it to moderation (product rule 3). Rejected files never reach a moderator.
 */
class ProcessSkillShowVideo implements ShouldQueue
{
    use Queueable;

    public const REASON_TOO_LONG = 'too_long';

    public const REASON_UNREADABLE = 'unreadable';

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(public SkillShow $skillShow) {}

    public function handle(SkillShowVideoProcessor $processor): void
    {
        $skillShow = $this->skillShow->fresh();

        if ($skillShow === null || $skillShow->moderation_status !== ModerationStatus::Processing) {
            return;
        }

        $upload = $skillShow->getFirstMedia(SkillShow::UPLOAD_COLLECTION);

        if ($upload === null) {
            $this->markFailed($skillShow, self::REASON_UNREADABLE);

            return;
        }

        $directory = storage_path('app/tmp/skillshow-'.Str::ulid());
        File::ensureDirectoryExists($directory);
        $source = "{$directory}/source";

        try {
            $stream = Storage::disk($upload->disk)->readStream($upload->getPathRelativeToRoot());
            file_put_contents($source, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $inspection = $processor->inspect($source);

            if ($inspection === null || ! $inspection['hasVideo'] || $inspection['durationMs'] <= 0) {
                $this->markFailed($skillShow, self::REASON_UNREADABLE);

                return;
            }

            if ($inspection['durationMs'] > SkillShow::MAX_DURATION_MS + SkillShow::DURATION_TOLERANCE_MS) {
                $this->markFailed($skillShow, self::REASON_TOO_LONG, $inspection['durationMs']);

                return;
            }

            $processor->convert($source, "{$directory}/video.mp4", "{$directory}/poster.jpg", $inspection['durationMs']);

            $skillShow->addMedia("{$directory}/video.mp4")->usingFileName('skillshow.mp4')->toMediaCollection(SkillShow::VIDEO_COLLECTION);
            $skillShow->addMedia("{$directory}/poster.jpg")->usingFileName('poster.jpg')->toMediaCollection(SkillShow::POSTER_COLLECTION);
            $skillShow->clearMediaCollection(SkillShow::UPLOAD_COLLECTION);

            $skillShow->update([
                'duration_ms' => min($inspection['durationMs'], SkillShow::MAX_DURATION_MS),
                'moderation_status' => ModerationStatus::Pending,
                'reason_code' => null,
            ]);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $skillShow = $this->skillShow->fresh();

        if ($skillShow !== null) {
            $this->markFailed($skillShow, self::REASON_UNREADABLE);
        }
    }

    private function markFailed(SkillShow $skillShow, string $reason, ?int $durationMs = null): void
    {
        $skillShow->clearMediaCollection(SkillShow::UPLOAD_COLLECTION);
        $skillShow->update([
            'moderation_status' => ModerationStatus::Failed,
            'reason_code' => $reason,
            'duration_ms' => $durationMs,
        ]);
    }
}
