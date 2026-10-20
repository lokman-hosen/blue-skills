<?php

namespace App\Domain\Jobs\Data;

use Spatie\LaravelData\Data;

/**
 * One verification indicator on the Truth Card. `key` maps to the translation
 * `truth_card.check.{key}`. Only checks that were actually performed are listed.
 */
class TruthCardCheckData extends Data
{
    public function __construct(
        public string $key,
        /** ok | warn | bad | neutral */
        public string $tone,
    ) {}
}
