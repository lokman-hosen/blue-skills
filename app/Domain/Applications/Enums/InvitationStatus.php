<?php

namespace App\Domain\Applications\Enums;

enum InvitationStatus: string
{
    case Sent = 'sent';
    case Applied = 'applied';
    case Declined = 'declined';
    case Expired = 'expired';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Waiting for answer',
            self::Applied => 'Applied',
            self::Declined => 'Declined',
            self::Expired => 'Expired',
        };
    }
}
