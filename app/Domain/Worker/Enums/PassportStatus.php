<?php

namespace App\Domain\Worker\Enums;

enum PassportStatus: string
{
    case None = 'none';
    case Applied = 'applied';
    case Ready = 'ready';
    case Expired = 'expired';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Applied => 'Applied',
            self::Ready => 'Ready',
            self::Expired => 'Expired',
        };
    }
}
