<?php

namespace App\Domain\Jobs\Enums;

/**
 * How a benefit reaches the worker. Shown as Free / Paid / Not provided (job posting prototype);
 * a null value is "Unknown" and reads "Not disclosed". Allowance stays for jobs that use it (D-117).
 */
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
            self::Provided => 'Free',
            self::Allowance => 'Allowance',
            self::Deducted => 'Paid',
            self::NotProvided => 'Not provided',
        };
    }
}
