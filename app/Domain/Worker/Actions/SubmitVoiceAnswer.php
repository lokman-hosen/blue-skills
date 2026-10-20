<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Jobs\ProcessVoiceAnswer;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Starts a Voice-to-CV answer from a recording or typed text and queues its processing.
 * An earlier unconfirmed answer to the same question is discarded with its recording.
 */
class SubmitVoiceAnswer
{
    public function __construct(private readonly DiscardVoiceAnswer $discard) {}

    public function recording(WorkerProfile $profile, VoiceQuestion $question, UploadedFile $audio, string $locale): VoiceAnswer
    {
        return $this->start($profile, $question, $locale, typed: false, text: null, audio: $audio);
    }

    public function typed(WorkerProfile $profile, VoiceQuestion $question, string $text, string $locale): VoiceAnswer
    {
        return $this->start($profile, $question, $locale, typed: true, text: $text, audio: null);
    }

    private function start(WorkerProfile $profile, VoiceQuestion $question, string $locale, bool $typed, ?string $text, ?UploadedFile $audio): VoiceAnswer
    {
        $answer = DB::transaction(function () use ($profile, $question, $locale, $typed, $text, $audio): VoiceAnswer {
            $profile->voiceAnswers()
                ->where('question_key', $question)
                ->whereNotIn('status', [VoiceAnswerStatus::Confirmed, VoiceAnswerStatus::Discarded])
                ->get()
                ->each(fn (VoiceAnswer $previous) => $this->discard->handle($previous));

            /** @var VoiceAnswer $answer */
            $answer = $profile->voiceAnswers()->create([
                'question_key' => $question,
                'locale' => $locale,
                'transcript' => $text,
                'typed' => $typed,
                // Typed text needs no speech-to-text, only extraction.
                'status' => $typed ? VoiceAnswerStatus::Transcribed : VoiceAnswerStatus::Pending,
            ]);

            if ($audio !== null) {
                $answer->addMedia($audio)
                    ->usingFileName('answer.'.($audio->guessExtension() ?? 'webm'))
                    ->toMediaCollection(VoiceAnswer::AUDIO_COLLECTION);
            }

            return $answer;
        });

        ProcessVoiceAnswer::dispatch($answer)->afterCommit();

        return $answer;
    }
}
