<?php

namespace App\Domain\Billing\States\Subscription;

class Cancelled extends SubscriptionState
{
    /** @var string */
    public static $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
