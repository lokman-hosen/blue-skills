<?php

namespace App\Domain\Worker\Voice;

use App\Domain\Config\Models\Country;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Worker\Contracts\ProfileExtractor;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use Normalizer;

/**
 * Keyword matching against the taxonomy, country names and VoiceVocabulary. Deliberately
 * conservative: anything it cannot find stays empty for the worker to fill in.
 */
class RuleBasedProfileExtractor implements ProfileExtractor
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    public function extract(VoiceQuestion $question, string $text, string $locale, ?string $occupationId): array
    {
        $text = $this->normalise($text);

        return match ($question) {
            VoiceQuestion::Occupation => $this->occupation($text),
            VoiceQuestion::Experience => ['years_experience' => $this->numbers($text)[0] ?? null],
            VoiceQuestion::Countries => ['countries' => $this->countriesWithYears($text)],
            VoiceQuestion::Skills => ['skills' => $this->skills($text, $occupationId)],
            VoiceQuestion::Passport => $this->passport($text),
            VoiceQuestion::Availability => ['availability' => $this->availability($text)?->value],
            VoiceQuestion::Destinations => ['destinations' => array_values(array_unique(array_column($this->countries($text, destinationsOnly: true), 'code')))],
        };
    }

    /**
     * @return array{occupation: string|null, specialization: string|null}
     */
    private function occupation(string $text): array
    {
        $occupations = Occupation::query()->get(['id', 'slug', 'name']);
        $aliases = $occupations->mapWithKeys(fn (Occupation $occupation) => [
            $occupation->slug => [$occupation->translatedName('en'), ...(VoiceVocabulary::OCCUPATIONS[$occupation->slug] ?? [])],
        ])->all();

        $slug = $this->firstMatch($text, $aliases);

        if ($slug === null) {
            return ['occupation' => null, 'specialization' => null];
        }

        $specializations = Specialization::query()
            ->where('occupation_id', $occupations->firstWhere('slug', $slug)?->id)
            ->get(['slug', 'name'])
            ->mapWithKeys(fn (Specialization $specialization) => [
                $specialization->slug => [$specialization->translatedName('en'), ...(VoiceVocabulary::SPECIALIZATIONS[$specialization->slug] ?? [])],
            ])->all();

        return ['occupation' => $slug, 'specialization' => $this->firstMatch($text, $specializations)];
    }

    /**
     * Countries in the order they were spoken, each paired with the n-th number spoken
     * ("five years in Bangladesh and three in Dubai" and "বাংলাদেশে পাঁচ বছর, দুবাইতে তিন বছর").
     *
     * @return list<array{code: string, years: int|null}>
     */
    private function countriesWithYears(string $text): array
    {
        $countries = $this->countries($text);
        $numbers = $this->numbers($text);

        return array_map(
            fn (array $country, int $index) => ['code' => $country['code'], 'years' => $numbers[$index] ?? null],
            $countries,
            array_keys($countries),
        );
    }

    /**
     * @return list<string>
     */
    private function skills(string $text, ?string $occupationId): array
    {
        $skills = Skill::query()
            ->when($occupationId, fn ($query) => $query->orderByRaw('occupation_id = ? desc', [$occupationId]))
            ->get(['slug', 'name']);

        $matches = [];

        foreach ($skills as $skill) {
            $position = $this->position($text, [$skill->translatedName('en'), ...(VoiceVocabulary::SKILLS[$skill->slug] ?? [])]);

            if ($position !== null) {
                $matches[$skill->slug] = $position;
            }
        }

        asort($matches);

        return array_keys($matches);
    }

    /**
     * @return array{passport_status: string|null, passport_valid_until_year: int|null}
     */
    private function passport(string $text): array
    {
        $year = collect($this->numbers($text, allowYears: true))->first(fn (int $number) => $number >= 2000 && $number <= 2100);

        $status = match (true) {
            $this->mentions($text, VoiceVocabulary::EXPIRED) => PassportStatus::Expired,
            $this->mentions($text, VoiceVocabulary::APPLIED) => PassportStatus::Applied,
            $year !== null && $year < (int) now()->year => PassportStatus::Expired,
            // "No, I do not have one": a no without any expiry year wins over "have".
            $year === null && $this->mentions($text, VoiceVocabulary::NO) => PassportStatus::None,
            $year !== null || $this->mentions($text, VoiceVocabulary::YES) => PassportStatus::Ready,
            default => null,
        };

        return ['passport_status' => $status?->value, 'passport_valid_until_year' => $year];
    }

    private function availability(string $text): ?Availability
    {
        if ($this->mentions($text, VoiceVocabulary::NOT_AVAILABLE)) {
            return Availability::NotAvailable;
        }

        if ($this->mentions($text, VoiceVocabulary::IMMEDIATELY)) {
            return Availability::Immediately;
        }

        $number = $this->numbers($text)[0] ?? ($this->mentions($text, VoiceVocabulary::MONTH) ? 1 : null);

        if ($number === null) {
            return null;
        }

        $days = $this->mentions($text, VoiceVocabulary::DAY) ? $number : $number * 30;

        return match (true) {
            $days <= 30 => Availability::Within30Days,
            $days <= 60 => Availability::Within60Days,
            $days <= 90 => Availability::Within90Days,
            default => null,
        };
    }

    /**
     * Countries mentioned, in order of first mention.
     *
     * @return list<array{code: string, position: int}>
     */
    private function countries(string $text, bool $destinationsOnly = false): array
    {
        $countries = Country::query()
            ->when($destinationsOnly, fn ($query) => $query->where('enabled_as_destination', true))
            ->get(['code', 'name']);

        $found = [];

        foreach ($countries as $country) {
            $position = $this->position($text, [$country->name, ...(VoiceVocabulary::COUNTRIES[$country->code] ?? [])]);

            if ($position !== null) {
                $found[] = ['code' => $country->code, 'position' => $position];
            }
        }

        usort($found, fn (array $a, array $b) => $a['position'] <=> $b['position']);

        return $found;
    }

    /**
     * Whole numbers in order, from digits or number words. Calendar years are skipped unless asked for.
     *
     * @return list<int>
     */
    private function numbers(string $text, bool $allowYears = false): array
    {
        $numbers = [];

        $words = [];

        foreach (VoiceVocabulary::NUMBER_WORDS as $word => $value) {
            $words[$this->normalise($word)] = $value;
        }

        foreach ($this->tokens($text) as $token) {
            $number = match (true) {
                ctype_digit($token) => (int) $token,
                default => $words[$token] ?? null,
            };

            if ($number !== null && ($allowYears || $number <= 60)) {
                $numbers[] = $number;
            }
        }

        return $numbers;
    }

    /**
     * @param  array<string, list<string>>  $aliasesByKey
     */
    private function firstMatch(string $text, array $aliasesByKey): ?string
    {
        $best = null;
        $bestPosition = PHP_INT_MAX;

        foreach ($aliasesByKey as $key => $aliases) {
            $position = $this->position($text, $aliases);

            if ($position !== null && $position < $bestPosition) {
                [$best, $bestPosition] = [$key, $position];
            }
        }

        return $best;
    }

    /**
     * Earliest position of any alias. Latin aliases match whole words; Bangla aliases match as
     * a prefix of a word so case endings still match (বাংলাদেশে, দুবাইতে).
     *
     * @param  list<string>  $aliases
     */
    private function position(string $text, array $aliases): ?int
    {
        $best = null;

        foreach ($aliases as $alias) {
            $alias = $this->normalise($alias);

            if ($alias === '') {
                continue;
            }

            $pattern = preg_match('/^[\x20-\x7e]+$/', $alias) === 1
                ? '/(?<![\p{L}\p{N}])'.preg_quote($alias, '/').'(?![\p{L}\p{N}])/u'
                : '/(?<![\p{L}\p{M}\p{N}])'.preg_quote($alias, '/').'/u';

            if (preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE) === 1) {
                $best = min($best ?? PHP_INT_MAX, $match[0][1]);
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $terms
     */
    private function mentions(string $text, array $terms): bool
    {
        $tokens = $this->tokens($text);

        foreach ($terms as $term) {
            $term = $this->normalise($term);

            if (str_contains($term, ' ') ? str_contains($text, $term) : in_array($term, $tokens, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function tokens(string $text): array
    {
        return preg_split("/[^\p{L}\p{M}\p{N}']+/u", $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Lower case, Latin digits and one Unicode form, so "য়" typed either way matches.
     */
    private function normalise(string $text): string
    {
        $text = Normalizer::normalize(trim($text), Normalizer::FORM_C) ?: trim($text);

        return mb_strtolower(str_replace(self::BANGLA_DIGITS, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $text));
    }
}
