<?php

namespace App\Domain\Config\Data;

use App\Domain\Config\Enums\LanguageStatus;
use App\Domain\Config\Models\Language;
use Spatie\LaravelData\Data;

class LocaleOptionData extends Data
{
    public function __construct(
        public string $code,
        public string $nativeName,
        public string $englishName,
        public bool $rtl,
        public LanguageStatus $status,
    ) {}

    public static function fromLanguage(Language $language): self
    {
        return new self(
            code: $language->code,
            nativeName: $language->native_name,
            englishName: $language->english_name,
            rtl: $language->rtl,
            status: $language->status,
        );
    }
}
