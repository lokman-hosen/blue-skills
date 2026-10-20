<?php

namespace App\Domain\Worker\Voice;

use App\Domain\Worker\Contracts\SpeechToText;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Exceptions\TranscriptionFailed;

/**
 * Bound when no speech-to-text provider is configured: workers type their answers.
 */
class UnavailableSpeechToText implements SpeechToText
{
    public function transcribe(string $audioPath, string $locale, VoiceQuestion $question): string
    {
        throw new TranscriptionFailed(TranscriptionFailed::UNAVAILABLE);
    }

    public function available(): bool
    {
        return false;
    }
}
