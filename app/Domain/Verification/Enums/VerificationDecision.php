<?php

namespace App\Domain\Verification\Enums;

enum VerificationDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case NeedsInfo = 'needs_info';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::NeedsInfo => 'Needs information',
        };
    }
}
