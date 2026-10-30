<?php

namespace App\Domain\Manual\Support;

use App\Domain\Manual\Data\ManualData;
use App\Domain\Manual\Data\ManualSectionData;
use App\Domain\Manual\Enums\ManualRole;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reads the user manuals from `resources/manuals/{locale}/{role}.md` (D-110). A manual is plain
 * Markdown: a `# ` title, an optional introduction, then one `## ` heading per section. A line
 * `@include shared/name` pulls in `resources/manuals/{locale}/shared/name.md`, so text that
 * several roles share is written once. Missing translations fall back to English.
 */
class ManualLibrary
{
    public const FALLBACK_LOCALE = 'en';

    private const MAX_INCLUDE_DEPTH = 3;

    public function __construct(private readonly ?string $root = null) {}

    public function exists(ManualRole $role, string $locale = self::FALLBACK_LOCALE): bool
    {
        return is_file($this->path($locale, $role->value));
    }

    public function load(ManualRole $role, string $locale): ManualData
    {
        $translated = $this->exists($role, $locale);
        $markdown = $this->read($translated ? $locale : self::FALLBACK_LOCALE, $role->value, 0);

        return $this->parse($role, $markdown, translationPending: ! $translated);
    }

    private function parse(ManualRole $role, string $markdown, bool $translationPending): ManualData
    {
        $title = $role->label();
        $intro = [];
        $sections = [];
        $current = null;

        foreach (preg_split('/\R/', $markdown) ?: [] as $line) {
            if ($current === null && $sections === [] && preg_match('/^#\s+(.+)$/', $line, $match) === 1) {
                $title = trim($match[1]);
            } elseif (preg_match('/^##\s+(.+)$/', $line, $match) === 1) {
                $current = count($sections);
                $sections[] = ['title' => trim($match[1]), 'lines' => []];
            } elseif ($current === null) {
                $intro[] = $line;
            } else {
                $sections[$current]['lines'][] = $line;
            }
        }

        $ids = [];

        return new ManualData(
            role: $role->value,
            label: __("role.{$role->value}"),
            group: $role->group(),
            title: $title,
            introHtml: $this->html(implode("\n", $intro)),
            sections: array_map(function (array $section) use (&$ids): ManualSectionData {
                $html = $this->html(implode("\n", $section['lines']));

                return new ManualSectionData(
                    id: $this->uniqueId($section['title'], $ids),
                    title: $section['title'],
                    html: $html,
                );
            }, $sections),
            translationPending: $translationPending,
        );
    }

    private function read(string $locale, string $name, int $depth): string
    {
        $path = $this->path($locale, $name);

        if (! is_file($path) && $locale !== self::FALLBACK_LOCALE) {
            $path = $this->path(self::FALLBACK_LOCALE, $name);
        }

        if (! is_file($path)) {
            throw new RuntimeException("Manual file [{$name}] is missing.");
        }

        $markdown = (string) file_get_contents($path);

        return (string) preg_replace_callback(
            '/^@include\s+([a-z0-9_\-]+(?:\/[a-z0-9_\-]+)*)\s*$/m',
            function (array $match) use ($locale, $depth): string {
                if ($depth >= self::MAX_INCLUDE_DEPTH) {
                    throw new RuntimeException("Manual include [{$match[1]}] is nested too deeply.");
                }

                return rtrim($this->read($locale, $match[1], $depth + 1));
            },
            $markdown,
        );
    }

    private function html(string $markdown): string
    {
        $markdown = trim($markdown);

        // Manuals are trusted repository files, but raw HTML is still stripped so the page
        // only ever renders Markdown.
        return $markdown === '' ? '' : Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * @param  array<string, true>  $ids
     */
    private function uniqueId(string $title, array &$ids): string
    {
        $base = Str::slug($title) ?: 'section';
        $id = $base;

        for ($n = 2; isset($ids[$id]); $n++) {
            $id = "{$base}-{$n}";
        }

        $ids[$id] = true;

        return $id;
    }

    private function path(string $locale, string $name): string
    {
        return ($this->root ?? resource_path('manuals'))."/{$locale}/{$name}.md";
    }
}
