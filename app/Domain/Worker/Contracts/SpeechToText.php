<?php

namespace App\Domain\Worker\Contracts;

use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Exceptions\TranscriptionFailed;

/**
 * Turns a worker's recorded answer into text (D-047). Providers plug in here; the worker
 * always sees and confirms the transcript before anything is saved (product rule 2).
 */
interface SpeechToText
{
    /**
     * @param  string  $audioPath  Local path of the recording.
     *
     * @throws TranscriptionFailed
     */
    public function transcribe(string $audioPath, string $locale, VoiceQuestion $question): string;

    /**
     * False when no provider is configured; the worker is then offered typing only.
     */
    public function available(): bool;
}
