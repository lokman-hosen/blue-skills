<?php

namespace App\Domain\Worker\Jobs;

use App\Domain\Worker\Contracts\ProfileExtractor;
use App\Domain\Worker\Contracts\SpeechToText;
use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Exceptions\TranscriptionFailed;
use App\Domain\Worker\Models\VoiceAnswer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Speech → transcript → suggested fields. The result waits for the worker to confirm or
 * edit it; nothing here touches the profile (product rule 2).
 */
class ProcessVoiceAnswer implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public VoiceAnswer $answer) {}

    public function handle(SpeechToText $speechToText, ProfileExtractor $extractor): void
    {
        $answer = $this->answer->fresh(['workerProfile']);

        if ($answer === null || ! $answer->status->isProcessing()) {
            return;
        }

        if ($answer->status === VoiceAnswerStatus::Pending && ! $answer->typed) {
            try {
                $transcript = $this->transcribe($answer, $speechToText);
            } catch (TranscriptionFailed $exception) {
                $answer->update(['status' => VoiceAnswerStatus::Failed, 'error_code' => $exception->reason]);

                return;
            }

            $answer->update(['transcript' => $transcript, 'status' => VoiceAnswerStatus::Transcribed]);
        }

        $answer->update([
            'extracted' => $extractor->extract($answer->question_key, (string) $answer->transcript, $answer->locale, $answer->workerProfile->occupation_id),
            'status' => VoiceAnswerStatus::Extracted,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->answer->update(['status' => VoiceAnswerStatus::Failed, 'error_code' => TranscriptionFailed::PROVIDER_ERROR]);
    }

    /**
     * @throws TranscriptionFailed
     */
    private function transcribe(VoiceAnswer $answer, SpeechToText $speechToText): string
    {
        $media = $answer->getFirstMedia(VoiceAnswer::AUDIO_COLLECTION);

        if ($media === null) {
            throw new TranscriptionFailed(TranscriptionFailed::NO_SPEECH);
        }

        // Copy to a local temporary file so any disk (local or object storage) works.
        $path = tempnam(sys_get_temp_dir(), 'voice-');
        $stream = Storage::disk($media->disk)->readStream($media->getPathRelativeToRoot());

        try {
            file_put_contents($path, $stream);
            $transcript = trim($speechToText->transcribe($path, $answer->locale, $answer->question_key));
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            @unlink($path);
        }

        if ($transcript === '') {
            throw new TranscriptionFailed(TranscriptionFailed::NO_SPEECH);
        }

        return $transcript;
    }
}
