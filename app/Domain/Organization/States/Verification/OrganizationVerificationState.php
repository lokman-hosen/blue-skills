<?php

namespace App\Domain\Organization\States\Verification;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Model>
 */
abstract class OrganizationVerificationState extends State
{
    abstract public function label(): string;

    /**
     * Design-system tone used by StatusChip: ok, warn, bad, brand, stamp or neutral.
     */
    abstract public function tone(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Unverified::class)
            ->allowTransition(Unverified::class, Pending::class)
            ->allowTransition(Pending::class, Verified::class)
            ->allowTransition(Pending::class, Rejected::class)
            ->allowTransition(Rejected::class, Pending::class)
            ->allowTransition(Verified::class, Suspended::class)
            ->allowTransition(Verified::class, Pending::class)
            ->allowTransition(Suspended::class, Verified::class);
    }
}
