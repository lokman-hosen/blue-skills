<?php

namespace App\Domain\Config\Support;

use App\Domain\Config\Data\LocaleOptionData;
use App\Domain\Config\Enums\LanguageStatus;
use App\Domain\Config\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Interface locales come from the languages table (managed by staff in Module 8).
 * Active and in-review languages are selectable; planned ones are not.
 */
class LocaleRegistry
{
    public const CACHE_KEY = 'blueskills.locales';

    /**
     * Cached as plain arrays: the cache never stores serialized objects.
     *
     * @return Collection<int, LocaleOptionData>
     */
    public function available(): Collection
    {
        /** @var list<array{code: string, native_name: string, english_name: string, rtl: bool, status: string}> $rows */
        $rows = Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => Language::query()
            ->whereIn('status', [LanguageStatus::Active, LanguageStatus::InReview])
            ->orderByRaw("code <> 'en'")
            ->orderBy('english_name')
            ->get()
            ->map(fn (Language $language) => [
                'code' => $language->code,
                'native_name' => $language->native_name,
                'english_name' => $language->english_name,
                'rtl' => $language->rtl,
                'status' => $language->status->value,
            ])
            ->all());

        return collect($rows)->map(fn (array $row) => new LocaleOptionData(
            code: $row['code'],
            nativeName: $row['native_name'],
            englishName: $row['english_name'],
            rtl: $row['rtl'],
            status: LanguageStatus::from($row['status']),
        ))->values();
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        $codes = $this->available()->pluck('code')->values()->all();

        return $codes === [] ? [$this->fallback()] : $codes;
    }

    public function supports(?string $code): bool
    {
        return $code !== null && in_array($code, $this->codes(), true);
    }

    public function find(string $code): ?LocaleOptionData
    {
        return $this->available()->firstWhere('code', $code);
    }

    public function isRtl(string $code): bool
    {
        return $this->find($code)->rtl ?? in_array($code, ['ar', 'ur'], true);
    }

    public function isTranslationPending(string $code): bool
    {
        return $this->find($code)?->status === LanguageStatus::InReview;
    }

    public function fallback(): string
    {
        return (string) config('app.fallback_locale', 'en');
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
