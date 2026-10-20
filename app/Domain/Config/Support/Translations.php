<?php

namespace App\Domain\Config\Support;

use Illuminate\Support\Facades\File;

/**
 * Interface strings for the React app: lang/{locale}.json merged over lang/en.json, so a
 * missing key falls back to English (docs/translation-todo.md lists the gaps).
 */
class Translations
{
    /** Unicode first-strong isolate / pop directional isolate. */
    private const ISOLATE_START = "\u{2068}";

    private const ISOLATE_END = "\u{2069}";

    public function __construct(private readonly LocaleRegistry $locales) {}

    /** @var array<string, array<string, string>> */
    private array $loaded = [];

    /**
     * @return array<string, string>
     */
    public function for(string $locale): array
    {
        $fallback = (string) config('app.fallback_locale', 'en');

        if ($locale === $fallback) {
            return $this->loaded[$locale] ??= $this->file($fallback);
        }

        $english = $this->file($fallback);

        // English shown inside a right-to-left page is isolated so its punctuation stays put.
        if ($this->locales->isRtl($locale)) {
            $english = array_map(fn (string $text) => self::ISOLATE_START.$text.self::ISOLATE_END, $english);
        }

        return $this->loaded[$locale] ??= array_merge($english, $this->file($locale));
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
