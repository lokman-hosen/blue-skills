<?php

namespace App\Domain\Jobs\Enums;

/**
 * The poster's declaration about worker-paid recruitment fees. Unknown is stored as null. "Apply"
 * needs at least one itemised worker cost; "None" allows none (product rule 4, D-117).
 */
enum WorkerFees: string
{
    case None = 'none';
    case Apply = 'apply';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Apply => 'Fees apply',
        };
    }
}
