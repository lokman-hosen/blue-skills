<?php

namespace App\Domain\Jobs\Enums;

enum CostPayer: string
{
    case Employer = 'employer';
    case Agency = 'agency';
    case Worker = 'worker';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Employer => 'Employer',
            self::Agency => 'Agency',
            self::Worker => 'Worker',
        };
    }
}
