<?php

namespace App\Domain\Organization\Data;

use Spatie\LaravelData\Data;

class TeamMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $title,
        /** Role name; label via `role.{name}` (created roles are in the shared strings too). */
        public ?string $role,
        public ?string $joinedAt,
        public bool $isYou,
    ) {}
}
