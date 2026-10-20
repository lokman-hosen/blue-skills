<?php

namespace App\Domain\Jobs\Enums;

enum BenefitProvision: string
{
    case Provided = 'provided';
    case Allowance = 'allowance';
    case Deducted = 'deducted';
    case NotProvided = 'not_provided';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Provided => 'Provided',
            self::Allowance => 'Allowance',
            self::Deducted => 'Deducted',
            self::NotProvided => 'Not provided',
        };
    }
}
