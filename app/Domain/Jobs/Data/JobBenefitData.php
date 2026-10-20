<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Enums\BenefitProvision;
use Spatie\LaravelData\Data;

/**
 * A null provision means the poster did not disclose it.
 */
class JobBenefitData extends Data
{
    public function __construct(
        /** accommodation | food | transport | medical | airfare */
        public string $key,
        public ?BenefitProvision $provision,
        public ?string $details,
    ) {}
}
