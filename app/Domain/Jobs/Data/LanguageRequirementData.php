<?php

namespace App\Domain\Jobs\Data;

use Spatie\LaravelData\Data;

class LanguageRequirementData extends Data
{
    public function __construct(
        public string $code,
        public string $level,
    ) {}
}
