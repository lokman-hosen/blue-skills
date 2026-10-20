<?php

namespace App\Domain\Worker\Data;

use Spatie\LaravelData\Data;

class ProfileStepData extends Data
{
    public function __construct(
        /** phone | trade | answers | skillshow | evidence */
        public string $key,
        public bool $done,
    ) {}
}
