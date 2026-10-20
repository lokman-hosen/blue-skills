<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Pending;
use App\Domain\Organization\States\Verification\Rejected;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\VerificationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * After a rejection the organization fixes its documents and asks for a new review.
 */
class ResubmitOrganizationReview
{
    public function handle(Organization $organization, User $requester): VerificationRequest
    {
        if (! $organization->verification_status->equals(Rejected::class)) {
            throw ValidationException::withMessages(['organization' => __('organization.resubmit_not_rejected')]);
        }

        return DB::transaction(function () use ($organization, $requester): VerificationRequest {
            $organization->verification_status->transitionTo(Pending::class);

            /** @var VerificationRequest $request */
            $request = $organization->verificationRequests()->create([
                'type' => match ($organization->type) {
                    OrganizationType::Agency => VerificationType::AgencyLicence,
                    OrganizationType::Institute => VerificationType::InstituteAccreditation,
                    OrganizationType::Employer => VerificationType::Organization,
                },
            ]);

            activity('organization')->performedOn($organization)->causedBy($requester)->log('verification.resubmitted');

            return $request;
        });
    }
}
