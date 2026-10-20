<?php

namespace App\Domain\Organization\Enums;

enum OrganizationType: string
{
    case Employer = 'employer';
    case Agency = 'agency';
    case Institute = 'institute';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Employer => 'Employer',
            self::Agency => 'Agency',
            self::Institute => 'Institute',
        };
    }
}
