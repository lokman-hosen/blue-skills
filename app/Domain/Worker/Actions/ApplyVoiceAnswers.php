<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Config\Models\Country;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Worker\Enums\SkillSource;
use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Support\ProfileCompletion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes the worker's confirmed answers to the Skill Passport (spec §3 phase C). Only
 * confirmed answers are ever read here (product rule 2).
 */
class ApplyVoiceAnswers
{
    public function __construct(private readonly ProfileCompletion $completion) {}

    /**
     * @return Collection<string, VoiceAnswer> Latest confirmed answer per question key.
     */
    public static function confirmedAnswers(WorkerProfile $profile): Collection
    {
        return $profile->voiceAnswers()
            ->where('status', VoiceAnswerStatus::Confirmed)
            ->latest('confirmed_at')
            ->get()
            ->unique(fn (VoiceAnswer $answer) => $answer->question_key->value)
            ->keyBy(fn (VoiceAnswer $answer) => $answer->question_key->value);
    }

    /**
     * @throws ValidationException when a question has no confirmed answer
     */
    public function handle(WorkerProfile $profile): WorkerProfile
    {
        $answers = self::confirmedAnswers($profile);
        $missing = array_filter(VoiceQuestion::cases(), fn (VoiceQuestion $question) => ! $answers->has($question->value));

        if ($missing !== []) {
            throw ValidationException::withMessages(['answers' => __('worker.voice.confirm_all')]);
        }

        /** @var array<string, array<string, mixed>> $fields */
        $fields = $answers->map(fn (VoiceAnswer $answer) => $answer->extracted ?? [])->all();

        DB::transaction(function () use ($profile, $answers, $fields): void {
            $occupation = Occupation::query()->where('slug', $fields['occupation']['occupation'])->firstOrFail();
            $specialization = $fields['occupation']['specialization'] === null ? null
                : Specialization::query()->where('occupation_id', $occupation->id)->where('slug', $fields['occupation']['specialization'])->value('id');

            $profile->update([
                'occupation_id' => $occupation->id,
                'specialization_id' => $specialization,
                'headline' => $profile->headline ?? $occupation->translatedName('en'),
                'years_experience' => $fields['experience']['years_experience'],
                'passport_status' => $fields['passport']['passport_status'],
                'passport_valid_until_year' => $fields['passport']['passport_valid_until_year'],
                'availability' => $fields['availability']['availability'],
            ]);

            $this->applyCountries($profile, $fields['countries']['countries'], $occupation->translatedName('en'));
            $this->applySkills($profile, $fields['skills']['skills'], $answers->get(VoiceQuestion::Skills->value));
            $this->applyDestinations($profile, $fields['destinations']['destinations']);
        });

        $this->completion->refresh($profile);

        activity('worker')->performedOn($profile)->causedBy($profile->user)
            ->withProperties(['answers' => $answers->map(fn (VoiceAnswer $answer) => $answer->id)->all()])
            ->log('voice.applied');

        return $profile;
    }

    /**
     * Years-only experience per country, as the worker said it. Dated entries the worker added
     * by hand are left alone.
     *
     * @param  list<array{code: string, years: int}>  $countries
     */
    private function applyCountries(WorkerProfile $profile, array $countries, string $role): void
    {
        $codes = array_column($countries, 'code');

        $profile->experiences()->whereNull('started_on')->whereNotIn('country_code', $codes)->delete();

        foreach ($countries as $country) {
            $profile->experiences()->updateOrCreate(
                ['country_code' => $country['code'], 'started_on' => null],
                ['years' => $country['years'], 'role' => $role],
            );
        }
    }

    /**
     * The confirmed list replaces the skills the worker entered before; skills attested by an
     * institute stay.
     *
     * @param  list<string>  $slugs
     */
    private function applySkills(WorkerProfile $profile, array $slugs, ?VoiceAnswer $answer): void
    {
        $source = $answer?->typed ? SkillSource::Typed : SkillSource::Voice;
        $skillIds = Skill::query()->whereIn('slug', $slugs)->pluck('id')->all();

        $profile->workerSkills()
            ->where('source', '!=', SkillSource::Institute)
            ->whereNotIn('skill_id', $skillIds)
            ->delete();

        $existing = $profile->workerSkills()->pluck('skill_id')->all();

        foreach (array_diff($skillIds, $existing) as $skillId) {
            $profile->workerSkills()->create(['skill_id' => $skillId, 'source' => $source, 'confirmed_at' => now()]);
        }
    }

    /**
     * @param  list<string>  $codes
     */
    private function applyDestinations(WorkerProfile $profile, array $codes): void
    {
        $profile->destinationPreferences()->delete();

        foreach (array_values(array_intersect($codes, Country::query()->pluck('code')->all())) as $index => $code) {
            $profile->destinationPreferences()->create(['country_code' => $code, 'priority' => $index + 1]);
        }
    }
}
