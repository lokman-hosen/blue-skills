<?php

namespace App\Domain\Applications\States\Application;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Model>
 */
abstract class ApplicationState extends State
{
    abstract public function label(): string;

    /**
     * Design-system tone used by StatusChip: ok, warn, bad, brand, stamp or neutral.
     */
    abstract public function tone(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Applied::class)
            ->allowTransition(Applied::class, Reviewed::class)
            ->allowTransition(Applied::class, Qualified::class)
            ->allowTransition(Applied::class, Shortlisted::class)
            ->allowTransition(Applied::class, Interview::class)
            ->allowTransition(Applied::class, TradeAssessment::class)
            ->allowTransition(Applied::class, Selected::class)
            ->allowTransition(Applied::class, Offer::class)
            ->allowTransition(Applied::class, Rejected::class)
            ->allowTransition(Applied::class, Withdrawn::class)
            ->allowTransition(Reviewed::class, Qualified::class)
            ->allowTransition(Reviewed::class, Shortlisted::class)
            ->allowTransition(Reviewed::class, Interview::class)
            ->allowTransition(Reviewed::class, TradeAssessment::class)
            ->allowTransition(Reviewed::class, Selected::class)
            ->allowTransition(Reviewed::class, Offer::class)
            ->allowTransition(Reviewed::class, Rejected::class)
            ->allowTransition(Reviewed::class, Withdrawn::class)
            ->allowTransition(Qualified::class, Shortlisted::class)
            ->allowTransition(Qualified::class, Interview::class)
            ->allowTransition(Qualified::class, TradeAssessment::class)
            ->allowTransition(Qualified::class, Selected::class)
            ->allowTransition(Qualified::class, Offer::class)
            ->allowTransition(Qualified::class, Rejected::class)
            ->allowTransition(Qualified::class, Withdrawn::class)
            ->allowTransition(Shortlisted::class, Interview::class)
            ->allowTransition(Shortlisted::class, TradeAssessment::class)
            ->allowTransition(Shortlisted::class, Selected::class)
            ->allowTransition(Shortlisted::class, Offer::class)
            ->allowTransition(Shortlisted::class, Rejected::class)
            ->allowTransition(Shortlisted::class, Withdrawn::class)
            ->allowTransition(Interview::class, TradeAssessment::class)
            ->allowTransition(Interview::class, Selected::class)
            ->allowTransition(Interview::class, Offer::class)
            ->allowTransition(Interview::class, Rejected::class)
            ->allowTransition(Interview::class, Withdrawn::class)
            ->allowTransition(TradeAssessment::class, Selected::class)
            ->allowTransition(TradeAssessment::class, Offer::class)
            ->allowTransition(TradeAssessment::class, Rejected::class)
            ->allowTransition(TradeAssessment::class, Withdrawn::class)
            ->allowTransition(Selected::class, Offer::class)
            ->allowTransition(Selected::class, Rejected::class)
            ->allowTransition(Selected::class, Withdrawn::class)
            ->allowTransition(Offer::class, Rejected::class)
            ->allowTransition(Offer::class, Withdrawn::class)
            ->allowTransition(Offer::class, Accepted::class)
            ->allowTransition(Offer::class, Declined::class);
    }
}
