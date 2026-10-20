<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Models\JobCost;
use App\Support\Data\MoneyData;
use Spatie\LaravelData\Data;

class JobCostData extends Data
{
    public function __construct(
        public JobCostCategory $category,
        public ?string $description,
        public MoneyData $amount,
        public CostPayer $payer,
    ) {}

    public static function fromCost(JobCost $cost): self
    {
        return new self(
            category: $cost->category,
            description: $cost->description,
            amount: new MoneyData($cost->amount_minor, $cost->currency),
            payer: $cost->payer,
        );
    }
}
