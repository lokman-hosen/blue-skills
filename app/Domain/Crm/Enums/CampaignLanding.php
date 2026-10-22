<?php

namespace App\Domain\Crm\Enums;

/**
 * Where a campaign link sends people.
 */
enum CampaignLanding: string
{
    case Join = 'join';
    case Jobs = 'jobs';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Join => 'Sign up',
            self::Jobs => 'Job search',
        };
    }
}
