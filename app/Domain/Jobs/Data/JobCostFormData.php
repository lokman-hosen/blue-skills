<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use Spatie\LaravelData\Data;

class JobCostFormData extends Data
{
    public function __construct(
        public JobCostCategory $category,
        public ?string $description,
        /** Whole currency units. */
        public int $amount,
        public string $currency,
        public CostPayer $payer,
    ) {}
}
