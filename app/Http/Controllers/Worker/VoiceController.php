<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Worker\Actions\ApplyVoiceAnswers;
use App\Domain\Worker\Actions\ConfirmVoiceAnswer;
use App\Domain\Worker\Actions\SubmitVoiceAnswer;
use App\Domain\Worker\Contracts\SpeechToText;
use App\Domain\Worker\Data\ProfileOptionsData;
use App\Domain\Worker\Data\VoiceAnswerData;
use App\Domain\Worker\Data\VoiceProgressData;
use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Models\WorkerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\ConfirmVoiceAnswerRequest;
use App\Http\Requests\Worker\SubmitVoiceAnswerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Voice-to-CV (spec §3): question → speak or type → transcript and what we understood →
 * confirm or edit → review → save to the Skill Passport.
 */
class VoiceController extends Controller
{
    use ResolvesWorkerProfile;

    public function show(Request $request, SpeechToText $speechToText): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $confirmed = ApplyVoiceAnswers::confirmedAnswers($profile);
        $question = VoiceQuestion::tryFrom($request->string('q')->toString())
            ?? collect(VoiceQuestion::cases())->first(fn (VoiceQuestion $question) => ! $confirmed->has($question->value))
            ?? VoiceQuestion::Occupation;

        $answer = $profile->voiceAnswers()
            ->where('question_key', $question)
            ->where('status', '!=', VoiceAnswerStatus::Discarded)
            ->latest()
            ->first();

        return Inertia::render('worker/voice/question', [
            'question' => $question,
            'progress' => $this->progress($profile),
            'answer' => $answer !== null ? VoiceAnswerData::fromAnswer($answer) : null,
            'startTyping' => $request->string('mode')->toString() === 'type',
            'voiceAvailable' => $speechToText->available(),
            'occupationSlug' => $profile->occupation?->slug,
            // Polling while an answer is processed reloads only `answer`, so this stays lazy.
            'options' => fn () => ProfileOptionsData::load(),
            'maxSeconds' => (int) config('blueskills.voice.max_recording_seconds'),
            'maxTypedCharacters' => (int) config('blueskills.voice.max_typed_characters'),
        ]);
    }

    public function submit(SubmitVoiceAnswerRequest $request, VoiceQuestion $question, SubmitVoiceAnswer $submit): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $audio = $request->file('audio');

        if ($audio instanceof UploadedFile) {
            $submit->recording($profile, $question, $audio, app()->getLocale());
        } else {
            $submit->typed($profile, $question, $request->string('text')->trim()->toString(), app()->getLocale());
        }

        return to_route('worker.voice.show', ['q' => $question->value]);
    }

    public function confirm(ConfirmVoiceAnswerRequest $request, VoiceQuestion $question, ConfirmVoiceAnswer $confirm): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $answer = null;

        if ($request->filled('answer_id')) {
            $answer = VoiceAnswer::query()->findOrFail($request->string('answer_id')->toString());
            Gate::authorize('update', $answer);
            abort_unless($answer->question_key === $question && $answer->status !== VoiceAnswerStatus::Discarded, 409);
        }

        $confirm->handle($profile, $question, $answer, $request->validated(), app()->getLocale());

        $next = collect(VoiceQuestion::cases())->first(
            fn (VoiceQuestion $candidate) => ! ApplyVoiceAnswers::confirmedAnswers($profile)->has($candidate->value),
        );

        return $next !== null
            ? to_route('worker.voice.show', ['q' => $next->value])
            : to_route('worker.voice.review');
    }

    public function review(Request $request): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $confirmed = ApplyVoiceAnswers::confirmedAnswers($profile);

        return Inertia::render('worker/voice/review', [
            'answers' => collect(VoiceQuestion::cases())
                ->map(fn (VoiceQuestion $question) => $confirmed->has($question->value)
                    ? VoiceAnswerData::fromAnswer($confirmed->get($question->value))
                    : null)
                ->filter()
                ->values()
                ->all(),
            'progress' => $this->progress($profile),
            'options' => ProfileOptionsData::load(),
        ]);
    }

    public function apply(Request $request, ApplyVoiceAnswers $apply): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $apply->handle($profile);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('worker.voice.saved')]);

        return to_route('worker.passport.show');
    }

    /**
     * @return list<VoiceProgressData>
     */
    private function progress(WorkerProfile $profile): array
    {
        $confirmed = ApplyVoiceAnswers::confirmedAnswers($profile);

        return array_map(
            fn (VoiceQuestion $question) => new VoiceProgressData($question, $confirmed->has($question->value)),
            VoiceQuestion::cases(),
        );
    }
}
