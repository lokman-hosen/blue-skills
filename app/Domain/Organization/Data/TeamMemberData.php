<?php

namespace App\Domain\Organization\Data;

use App\Domain\Identity\Enums\OrganizationRole;
use Spatie\LaravelData\Data;

class TeamMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $title,
        public ?OrganizationRole $role,
        public ?string $joinedAt,
        public bool $isYou,
    ) {}
}
