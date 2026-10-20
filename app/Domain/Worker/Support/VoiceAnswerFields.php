<?php

namespace App\Domain\Worker\Support;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use Illuminate\Validation\Rule;

/**
 * The structured fields each Voice-to-CV question produces, in the stored
 * `voice_answers.extracted` shape. Used to validate what the worker confirms.
 */
final class VoiceAnswerFields
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function rules(VoiceQuestion $question, array $input): array
    {
        return match ($question) {
            VoiceQuestion::Occupation => [
                'occupation' => ['required', 'string', Rule::exists('occupations', 'slug')],
                'specialization' => [
                    'nullable', 'string',
                    Rule::exists('specializations', 'slug')->where(
                        'occupation_id',
                        Occupation::query()->where('slug', is_string($input['occupation'] ?? null) ? $input['occupation'] : '')->value('id'),
                    ),
                ],
            ],
            VoiceQuestion::Experience => [
                'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            ],
            VoiceQuestion::Countries => [
                'countries' => ['required', 'array', 'min:1', 'max:10'],
                'countries.*.code' => ['required', 'string', 'distinct', Rule::exists('countries', 'code')],
                'countries.*.years' => ['required', 'integer', 'min:0', 'max:60'],
            ],
            VoiceQuestion::Skills => [
                'skills' => ['required', 'array', 'min:1', 'max:20'],
                'skills.*' => ['required', 'string', 'distinct', Rule::exists('skills', 'slug')],
            ],
            VoiceQuestion::Passport => [
                'passport_status' => ['required', Rule::enum(PassportStatus::class)],
                'passport_valid_until_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            ],
            VoiceQuestion::Availability => [
                'availability' => ['required', Rule::enum(Availability::class)],
            ],
            VoiceQuestion::Destinations => [
                'destinations' => ['required', 'array', 'min:1', 'max:5'],
                'destinations.*' => ['required', 'string', 'distinct', Rule::exists('countries', 'code')->where('enabled_as_destination', true)],
            ],
        };
    }

    /**
     * Keeps only the question's own keys, typed as stored.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function normalise(VoiceQuestion $question, array $validated): array
    {
        return match ($question) {
            VoiceQuestion::Occupation => [
                'occupation' => $validated['occupation'],
                'specialization' => $validated['specialization'] ?? null,
            ],
            VoiceQuestion::Experience => ['years_experience' => (int) $validated['years_experience']],
            VoiceQuestion::Countries => [
                'countries' => array_values(array_map(
                    fn (array $country) => ['code' => $country['code'], 'years' => (int) $country['years']],
                    $validated['countries'],
                )),
            ],
            VoiceQuestion::Skills => ['skills' => array_values($validated['skills'])],
            VoiceQuestion::Passport => [
                'passport_status' => $validated['passport_status'],
                'passport_valid_until_year' => isset($validated['passport_valid_until_year']) ? (int) $validated['passport_valid_until_year'] : null,
            ],
            VoiceQuestion::Availability => ['availability' => $validated['availability']],
            VoiceQuestion::Destinations => ['destinations' => array_values($validated['destinations'])],
        };
    }
}
