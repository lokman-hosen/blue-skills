<?php

namespace App\Domain\Verification\States\VerificationRequest;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Model>
 */
abstract class VerificationRequestState extends State
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
            ->allowTransition(Open::class, InReview::class)
            ->allowTransition(InReview::class, NeedsInfo::class)
            ->allowTransition(NeedsInfo::class, InReview::class)
            ->allowTransition(InReview::class, Approved::class)
            ->allowTransition(InReview::class, Rejected::class);
    }
}
