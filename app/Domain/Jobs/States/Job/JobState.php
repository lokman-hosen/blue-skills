<?php

namespace App\Domain\Jobs\States\Job;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Model>
 */
abstract class JobState extends State
{
    abstract public function label(): string;

    /**
     * Design-system tone used by StatusChip: ok, warn, bad, brand, stamp or neutral.
     */
    abstract public function tone(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Submitted::class)
            ->allowTransition(Submitted::class, UnderReview::class)
            ->allowTransition(UnderReview::class, Published::class)
            ->allowTransition(UnderReview::class, MoreInformationRequested::class)
            ->allowTransition(UnderReview::class, Rejected::class)
            ->allowTransition(MoreInformationRequested::class, Submitted::class)
            ->allowTransition(Rejected::class, Draft::class)
            ->allowTransition(Published::class, Submitted::class)
            ->allowTransition(Paused::class, Submitted::class)
            ->allowTransition(Published::class, Paused::class)
            ->allowTransition(Paused::class, Published::class)
            ->allowTransition(Published::class, Closed::class)
            ->allowTransition(Paused::class, Closed::class)
            ->allowTransition(Published::class, Suspended::class)
            ->allowTransition(Paused::class, Suspended::class)
            ->allowTransition(Suspended::class, Published::class)
            ->allowTransition(Suspended::class, Closed::class)
            ->allowTransition(Closed::class, Archived::class)
            ->allowTransition(Draft::class, Archived::class);
    }
}
