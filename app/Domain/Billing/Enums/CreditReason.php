<?php

namespace App\Domain\Billing\Enums;

/**
 * Why posting credits were added or used. Entries are append-only.
 */
enum CreditReason: string
{
    case PlanGrant = 'plan_grant';
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case JobPublished = 'job_published';
    case Expiry = 'expiry';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::PlanGrant => 'Plan credits',
            self::Purchase => 'Purchase',
            self::Adjustment => 'Adjustment',
            self::JobPublished => 'Job published',
            self::Expiry => 'Expiry',
        };
    }
}
