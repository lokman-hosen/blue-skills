<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\VoiceAnswer;

/**
 * Deletes recordings the worker never confirmed or discarded once they are older than the
 * retention period (D-047). Transcripts stay.
 */
class PruneVoiceRecordings
{
    /**
     * @return int Number of recordings deleted.
     */
    public function handle(): int
    {
        $count = 0;

        VoiceAnswer::query()
            ->where('created_at', '<', now()->subDays((int) config('blueskills.voice.recording_retention_days')))
            ->whereHas('media', fn ($query) => $query->where('collection_name', VoiceAnswer::AUDIO_COLLECTION))
            ->each(function (VoiceAnswer $answer) use (&$count): void {
                $answer->clearMediaCollection(VoiceAnswer::AUDIO_COLLECTION);
                $count++;
            });

        return $count;
    }
}
