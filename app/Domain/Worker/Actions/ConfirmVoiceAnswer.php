<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Support\VoiceAnswerFields;
use Illuminate\Support\Facades\DB;

/**
 * The worker confirms (or corrects) what was understood. The confirmed values replace the
 * suggestion and the recording is deleted (D-047). The profile changes only in
 * ApplyVoiceAnswers.
 */
class ConfirmVoiceAnswer
{
    public function __construct(private readonly DiscardVoiceAnswer $discard) {}

    /**
     * @param  VoiceAnswer|null  $answer  Null when the worker filled the fields in directly.
     * @param  array<string, mixed>  $fields  Validated with VoiceAnswerFields::rules().
     */
    public function handle(WorkerProfile $profile, VoiceQuestion $question, ?VoiceAnswer $answer, array $fields, string $locale): VoiceAnswer
    {
        $confirmed = VoiceAnswerFields::normalise($question, $fields);

        $answer = DB::transaction(function () use ($profile, $question, $answer, $confirmed, $locale): VoiceAnswer {
            $answer ??= $profile->voiceAnswers()->create([
                'question_key' => $question,
                'locale' => $locale,
                'typed' => true,
                'status' => VoiceAnswerStatus::Extracted,
            ]);

            $edited = $confirmed !== array_intersect_key($answer->extracted ?? [], $confirmed);

            // One answer per question stays open: the newest confirmed one.
            $profile->voiceAnswers()
                ->where('question_key', $question)
                ->whereKeyNot($answer->id)
                ->where('status', '!=', VoiceAnswerStatus::Discarded)
                ->get()
                ->each(fn (VoiceAnswer $other) => $this->discard->handle($other));

            $answer->update([
                'extracted' => $confirmed,
                'status' => VoiceAnswerStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            activity('worker')->performedOn($answer)->causedBy($profile->user)
                ->withProperties(['question' => $question->value, 'edited' => $edited, 'typed' => $answer->typed])
                ->log('voice.confirmed');

            return $answer;
        });

        $answer->clearMediaCollection(VoiceAnswer::AUDIO_COLLECTION);

        return $answer;
    }
}
