<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Models\Offer;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Support\Data\MoneyData;
use Spatie\LaravelData\Data;

class OfferData extends Data
{
    public function __construct(
        public string $id,
        public MoneyData $salary,
        public SalaryPeriod $salaryPeriod,
        public ?string $startOn,
        public ?string $respondBy,
        public ?string $note,
        public OfferStatus $status,
        public string $sentAt,
        public ?string $respondedAt,
    ) {}

    public static function fromOffer(Offer $offer): self
    {
        return new self(
            id: $offer->id,
            salary: new MoneyData($offer->salary_amount_minor, $offer->salary_currency),
            salaryPeriod: $offer->salary_period,
            startOn: $offer->start_on?->toDateString(),
            respondBy: $offer->respond_by?->toDateString(),
            note: $offer->note,
            status: $offer->status,
            sentAt: $offer->created_at?->toIso8601String() ?? '',
            respondedAt: $offer->responded_at?->toIso8601String(),
        );
    }
}
