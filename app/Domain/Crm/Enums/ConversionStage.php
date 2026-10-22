<?php

namespace App\Domain\Crm\Enums;

/**
 * How far a lead has come, worked out from what the worker actually did (D-095). Ordered: a lead
 * only moves forward.
 */
enum ConversionStage: string
{
    case Prospect = 'prospect';
    case Registered = 'registered';
    case ProfileComplete = 'profile_complete';
    case Qualified = 'qualified';
    case Applied = 'applied';
    case Selected = 'selected';
    case Deployed = 'deployed';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Not signed up',
            self::Registered => 'Signed up',
            self::ProfileComplete => 'Profile complete',
            self::Qualified => 'Qualified',
            self::Applied => 'Applied',
            self::Selected => 'Selected',
            self::Deployed => 'Deployed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Prospect => 'neutral',
            self::Registered, self::ProfileComplete => 'brand',
            self::Qualified, self::Applied => 'stamp',
            self::Selected, self::Deployed => 'ok',
        };
    }

    public function rank(): int
    {
        return (int) array_search($this, self::cases(), true);
    }
}
