<?php

namespace App\Domain\Worker\Data;

use Spatie\LaravelData\Data;

class VoiceCountryData extends Data
{
    public function __construct(
        public string $code,
        public ?int $years,
    ) {}
}
