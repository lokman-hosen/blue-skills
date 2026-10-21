<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use Spatie\LaravelData\Data;

/**
 * A mandate an agency can post a job under, with the scope the wizard limits the job to (D-077).
 */
class MandateOptionData extends Data
{
    /**
     * @param  list<string>  $occupationIds  Empty when the mandate covers every trade.
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $employer,
        public array $occupationIds,
        public ?string $destinationCountryCode,
        public int $headcountLeft,
        public ?string $validUntil,
        public bool $usable,
    ) {}

    public static function fromMandate(JobMandate $mandate, ?JobPosting $job = null): self
    {
        return new self(
            id: $mandate->id,
            reference: $mandate->reference,
            employer: $mandate->employer->display_name,
            occupationIds: array_values($mandate->occupation_ids ?? []),
            destinationCountryCode: $mandate->destination_country_code,
            headcountLeft: $mandate->headcountLeft($job),
            validUntil: $mandate->valid_until?->toDateString(),
            usable: $mandate->isUsable(),
        );
    }
}
