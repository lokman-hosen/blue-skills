<?php

namespace App\Domain\Trust\States\Report;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Model>
 */
abstract class ReportState extends State
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
            ->allowTransition(Open::class, Dismissed::class)
            ->allowTransition(Investigating::class, Actioned::class)
            ->allowTransition(Investigating::class, Dismissed::class);
    }
}
