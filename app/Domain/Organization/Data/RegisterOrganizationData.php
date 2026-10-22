<?php

namespace App\Domain\Organization\Data;

use App\Domain\Organization\Enums\OrganizationType;
use Spatie\LaravelData\Data;

class RegisterOrganizationData extends Data
{
    public function __construct(
        public OrganizationType $type,
        public string $legalName,
        public string $displayName,
        public string $countryCode,
        public ?string $city,
        public ?string $registrationNumber,
        public ?string $licenceNumber,
        public ?string $licenceExpiresOn,
        public ?string $title,
        // Required while the pilot is on (D-108).
        public ?string $pilotCode = null,
    ) {}
}
