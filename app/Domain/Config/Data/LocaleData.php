<?php

namespace App\Domain\Config\Data;

use Spatie\LaravelData\Data;

class LocaleData extends Data
{
    public function __construct(
        public string $code,
        /** ltr | rtl */
        public string $dir,
        /** True while the locale's interface strings await human translation review. */
        public bool $translationPending,
    ) {}
}
