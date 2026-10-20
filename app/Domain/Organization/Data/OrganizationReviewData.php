<?php

namespace App\Domain\Organization\Data;

use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Models\VerificationRequest;
use App\Support\Data\StatusData;
use LogicException;
use Spatie\LaravelData\Data;

/**
 * One organization registration in the staff review queue.
 */
class OrganizationReviewData extends Data
{
    public function __construct(
        public string $reviewId,
        public StatusData $reviewStatus,
        public string $organizationId,
        public string $displayName,
        public string $legalName,
        public OrganizationType $type,
        public string $countryCode,
        public ?string $city,
        public ?string $registrationNumber,
        public ?string $licenceNumber,
        public ?string $licenceExpiresOn,
        public StatusData $verification,
        /** Intake flags such as a free-webmail sign-up (D-041). */
        public ?string $flags,
        public int $evidenceCount,
        public ?string $submittedAt,
    ) {}

    public static function fromRequest(VerificationRequest $request): self
    {
        $organization = $request->subject;

        if (! $organization instanceof Organization) {
            throw new LogicException('An organization review must have an organization subject.');
        }

        return new self(
            reviewId: $request->id,
            reviewStatus: StatusData::fromState($request->status),
            organizationId: $organization->id,
            displayName: $organization->display_name,
            legalName: $organization->legal_name,
            type: $organization->type,
            countryCode: $organization->country_code,
            city: $organization->city,
            registrationNumber: $organization->registration_number,
            licenceNumber: $organization->licence_number,
            licenceExpiresOn: $organization->licence_expires_on?->toDateString(),
            verification: StatusData::fromState($organization->verification_status),
            flags: $request->notes,
            evidenceCount: $request->getMedia(VerificationRequest::EVIDENCE_COLLECTION)->count(),
            submittedAt: $request->created_at?->toIso8601String(),
        );
    }
}
