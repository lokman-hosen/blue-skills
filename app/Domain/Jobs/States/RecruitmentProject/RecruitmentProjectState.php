<?php

namespace App\Domain\Jobs\States\RecruitmentProject;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * active → completed | cancelled; a completed project can be reopened.
 *
 * @extends State<Model>
 */
abstract class RecruitmentProjectState extends State
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
            ->allowTransition(Active::class, Completed::class)
            ->allowTransition(Active::class, Cancelled::class)
            ->allowTransition(Completed::class, Active::class);
    }
}
