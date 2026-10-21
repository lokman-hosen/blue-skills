<?php

namespace App\Domain\Trust\States\TrustCase;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * open → investigating → action taken → closed; a closed case can be reopened for
 * investigation (spec §10).
 *
 * @extends State<Model>
 */
abstract class TrustCaseState extends State
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
            ->allowTransition(Open::class, Investigating::class)
            ->allowTransition(Investigating::class, ActionTaken::class)
            ->allowTransition(Open::class, Closed::class)
            ->allowTransition(Investigating::class, Closed::class)
            ->allowTransition(ActionTaken::class, Closed::class)
            ->allowTransition(Closed::class, Investigating::class);
    }
}
