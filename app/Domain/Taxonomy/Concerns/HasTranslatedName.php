<?php

namespace App\Domain\Taxonomy\Concerns;

/**
 * `name` is a jsonb map of locale => text. Missing locales fall back to English.
 *
 * @property array<string, string> $name
 */
trait HasTranslatedName
{
    public function initializeHasTranslatedName(): void
    {
        $this->mergeCasts(['name' => 'array']);
    }

    public function translatedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->name[$locale] ?? $this->name[config('app.fallback_locale')] ?? '';
    }
}
