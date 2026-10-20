<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Models\VoiceAnswer;

/**
 * Drops an answer the worker did not want. The recording is deleted straight away (D-047).
 */
class DiscardVoiceAnswer
{
    public function handle(VoiceAnswer $answer): void
    {
        $answer->clearMediaCollection(VoiceAnswer::AUDIO_COLLECTION);
        $answer->update(['status' => VoiceAnswerStatus::Discarded]);
    }
}
