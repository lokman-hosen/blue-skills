<?php

namespace App\Domain\Billing\States\Subscription;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * active → cancelled | ended. An organization has at most one active subscription.
 *
 * @extends State<Model>
 */
abstract class SubscriptionState extends State
{
    abstract public function label(): string;

    /**
     * Design-system tone used by StatusChip: ok, warn, bad, brand, stamp or neutral.
     */
    abstract public function tone(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Active::class)
            ->allowTransition(Active::class, Cancelled::class)
            ->allowTransition(Active::class, Ended::class);
    }
}
