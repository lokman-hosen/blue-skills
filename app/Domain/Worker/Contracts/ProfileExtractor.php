<?php

namespace App\Domain\Worker\Contracts;

use App\Domain\Worker\Enums\VoiceQuestion;

/**
 * Reads structured Skill Passport fields out of an answer's text (D-047). The result is only
 * a suggestion: the worker confirms or edits it before anything reaches the profile.
 */
interface ProfileExtractor
{
    /**
     * Fields use the stored `voice_answers.extracted` shape, e.g. occupation and specialization
     * slugs, `years_experience`, `countries` [{code, years}], skill slugs, `passport_status`,
     * `passport_valid_until_year`, `availability`, destination codes. A field the text does not
     * clearly contain is null or an empty list, never a guess.
     *
     * @return array<string, mixed>
     */
    public function extract(VoiceQuestion $question, string $text, string $locale, ?string $occupationId): array;
}
