<?php

namespace App\Domain\Crm\States\Campaign;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * active ⇄ paused → ended. Ended campaign links still open the landing page but count no visits.
 *
 * @extends State<Model>
 */
abstract class CampaignState extends State
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
            ->allowTransition(Active::class, Paused::class)
            ->allowTransition(Paused::class, Active::class)
            ->allowTransition(Active::class, Ended::class)
            ->allowTransition(Paused::class, Ended::class);
    }
}
