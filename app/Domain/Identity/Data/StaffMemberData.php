<?php

namespace App\Domain\Identity\Data;

use App\Domain\Identity\Enums\StaffRole;
use Spatie\LaravelData\Data;

class StaffMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?StaffRole $role,
        public bool $twoFactorEnabled,
        public bool $isYou,
    ) {}
}
