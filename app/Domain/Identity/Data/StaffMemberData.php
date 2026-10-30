<?php

namespace App\Domain\Identity\Data;

use Spatie\LaravelData\Data;

class StaffMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        /** Role name; label via `role.{name}` (created roles are in the shared strings too). */
        public ?string $role,
        public bool $twoFactorEnabled,
        public bool $isYou,
    ) {}
}
