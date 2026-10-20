<?php

namespace App\Domain\Identity\Enums;

enum UserType: string
{
    case Worker = 'worker';
    case Org = 'org';
    case Staff = 'staff';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Worker => 'Worker',
            self::Org => 'Organization',
            self::Staff => 'Staff',
        };
    }
}
