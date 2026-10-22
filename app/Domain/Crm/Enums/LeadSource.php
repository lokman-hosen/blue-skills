<?php

namespace App\Domain\Crm\Enums;

/**
 * How a lead first reached BLUESKILLS.
 */
enum LeadSource: string
{
    case Campaign = 'campaign';
    case Institute = 'institute';
    case Direct = 'direct';
    case WalkIn = 'walk_in';
    case CallCentre = 'call_centre';
    case Referral = 'referral';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Campaign => 'Campaign',
            self::Institute => 'Institute invite',
            self::Direct => 'Signed up directly',
            self::WalkIn => 'Walk-in',
            self::CallCentre => 'Call centre',
            self::Referral => 'Referral',
        };
    }
}
