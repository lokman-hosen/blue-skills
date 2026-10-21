<?php

namespace App\Domain\Jobs\Data;

use Spatie\LaravelData\Data;

class MatchRequirementData extends Data
{
    /**
     * @param  array<string, string|int>  $params  Values for the translated label, e.g. skill name.
     */
    public function __construct(
        /** occupation | experience | skill | language | passport */
        public string $key,
        public array $params,
        public bool $met,
    ) {}
}
