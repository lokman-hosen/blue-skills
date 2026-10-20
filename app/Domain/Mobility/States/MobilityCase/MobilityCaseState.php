<?php

namespace App\Domain\Mobility\States\MobilityCase;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Model>
 */
abstract class MobilityCaseState extends State
{
    abstract public function label(): string;

    /**
     * Design-system tone used by StatusChip: ok, warn, bad, brand, stamp or neutral.
     */
    abstract public function tone(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Open::class)
            ->allowTransition(Open::class, Completed::class)
            ->allowTransition(Open::class, Cancelled::class);
    }
}
