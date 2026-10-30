<?php

namespace App\Domain\Identity\Data;

use Spatie\LaravelData\Data;

class RolePermissionData extends Data
{
    public function __construct(
        public string $name,
        public string $label,
        public string $hint,
        public bool $granted,
        /** Held by default in code. */
        public bool $default,
        /** Why it can't be changed for this role, already translated; null when it can. */
        public ?string $lockedReason,
    ) {}
}
