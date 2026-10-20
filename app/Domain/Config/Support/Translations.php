<?php

namespace App\Domain\Config\Support;

use Illuminate\Support\Facades\File;

/**
 * Interface strings for the React app: lang/{locale}.json merged over lang/en.json, so a
 * missing key falls back to English (docs/translation-todo.md lists the gaps).
 */
class Translations
{
    /** @var array<string, array<string, string>> */
    private array $loaded = [];

    /**
     * @return array<string, string>
     */
    public function for(string $locale): array
    {
        $fallback = (string) config('app.fallback_locale', 'en');

        return $this->loaded[$locale] ??= $locale === $fallback
            ? $this->file($fallback)
            : array_merge($this->file($fallback), $this->file($locale));
    }

    /**
     * @return array<string, string>
     */
    private function file(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            return [];
        }

        /** @var array<string, string> $strings */
        $strings = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        return $strings;
    }
}
