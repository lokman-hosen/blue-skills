<?php

namespace App\Domain\Jobs\Data;

use Spatie\LaravelData\Data;

/**
 * "X of Y requirements met" with every requirement listed (product rule 12). A guide for the
 * worker, never a ranking or a decision.
 */
class MatchSummaryData extends Data
{
    /**
     * @param  list<MatchRequirementData>  $requirements
     */
    public function __construct(
        public int $met,
        public int $total,
        public array $requirements,
    ) {}
}
