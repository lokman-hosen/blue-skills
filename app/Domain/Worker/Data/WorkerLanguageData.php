<?php

namespace App\Domain\Worker\Data;

use App\Domain\Worker\Enums\LanguageProficiency;
use App\Domain\Worker\Models\WorkerLanguage;
use Spatie\LaravelData\Data;

class WorkerLanguageData extends Data
{
    public function __construct(
        public string $code,
        public string $name,
        public LanguageProficiency $proficiency,
    ) {}

    public static function fromLanguage(WorkerLanguage $language): self
    {
        return new self(
            code: $language->language_code,
            name: $language->language->native_name ?? $language->language_code,
            proficiency: $language->proficiency,
        );
    }
}
