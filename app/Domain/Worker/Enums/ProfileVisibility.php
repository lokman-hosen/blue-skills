<?php

namespace App\Domain\Worker\Enums;

enum ProfileVisibility: string
{
    case EmployersAndAgencies = 'employers_and_agencies';
    case ApplicationsOnly = 'applications_only';
    case Hidden = 'hidden';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::EmployersAndAgencies => 'Employers & agencies',
            self::ApplicationsOnly => 'Applications only',
            self::Hidden => 'Hidden',
        };
    }
}
