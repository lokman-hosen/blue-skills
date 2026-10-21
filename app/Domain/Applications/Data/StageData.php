<?php

namespace App\Domain\Applications\Data;

use Spatie\LaravelData\Data;

class StageData extends Data
{
    public function __construct(
        /** An application status value, translated via `status.{key}`. */
        public string $key,
        /** done | current | pending | blocked | skipped */
        public string $status,
        public ?string $date,
    ) {}
}
