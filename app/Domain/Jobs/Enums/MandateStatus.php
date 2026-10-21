<?php

namespace App\Domain\Jobs\Enums;

/**
 * awaiting_employer → (employer confirms) pending → verified | rejected by staff (D-074);
 * declined when the employer says no.
 */
enum MandateStatus: string
{
    case AwaitingEmployer = 'awaiting_employer';
    case Declined = 'declined';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Revoked = 'revoked';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::AwaitingEmployer => 'Waiting for the employer',
            self::Declined => 'Declined by the employer',
            self::Pending => 'Waiting for BLUESKILLS review',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
        };
    }

    /**
     * Design-system tone for StatusChip.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Verified => 'ok',
            self::AwaitingEmployer, self::Pending => 'warn',
            self::Declined, self::Rejected, self::Revoked => 'bad',
            self::Expired => 'neutral',
        };
    }
}
