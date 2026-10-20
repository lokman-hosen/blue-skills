<?php

namespace App\Domain\Organization\Data;

use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

class OrganizationSummaryData extends Data
{
    public function __construct(
        public string $id,
        public string $displayName,
        public OrganizationType $type,
        public string $countryCode,
        public ?string $city,
        public StatusData $verification,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        return new self(
            id: $organization->id,
            displayName: $organization->display_name,
            type: $organization->type,
            countryCode: $organization->country_code,
            city: $organization->city,
            verification: StatusData::fromState($organization->verification_status),
        );
    }
}
