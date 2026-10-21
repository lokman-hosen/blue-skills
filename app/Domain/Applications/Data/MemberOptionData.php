<?php

namespace App\Domain\Applications\Data;

use Spatie\LaravelData\Data;

class MemberOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
