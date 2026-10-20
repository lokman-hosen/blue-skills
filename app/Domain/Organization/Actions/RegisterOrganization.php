<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Data\RegisterOrganizationData;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Pending;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\States\VerificationRequest\Open;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Self-registration (D-036): the organization starts pending, the registering user becomes its
 * owner and a verification request is queued for staff.
 */
class RegisterOrganization
{
    public function handle(User $user, RegisterOrganizationData $data): Organization
    {
        return DB::transaction(function () use ($user, $data): Organization {
            $organization = Organization::query()->create([
                'type' => $data->type,
                'legal_name' => $data->legalName,
                'display_name' => $data->displayName,
                'country_code' => $data->countryCode,
                'city' => $data->city,
                'registration_number' => $data->registrationNumber,
                'licence_number' => $data->type === OrganizationType::Agency ? $data->licenceNumber : null,
                'licence_expires_on' => $data->type === OrganizationType::Agency ? $data->licenceExpiresOn : null,
                'verification_status' => Pending::class,
                'data_region' => config('blueskills.default_data_region'),
            ]);

            $organization->users()->attach($user->id, ['title' => $data->title, 'joined_at' => now()]);
            TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->assignRole(OrganizationRole::ownerFor($data->type)->value));

            $organization->verificationRequests()->create([
                'type' => match ($data->type) {
                    OrganizationType::Employer => VerificationType::Organization,
                    OrganizationType::Agency => VerificationType::AgencyLicence,
                    OrganizationType::Institute => VerificationType::InstituteAccreditation,
                },
                'status' => Open::class,
                'notes' => $this->reviewerNotes($user),
            ]);

            activity('organization')->performedOn($organization)->causedBy($user)
                ->withProperties(['type' => $data->type->value])
                ->log('organization.registered');

            return $organization;
        });
    }

    /**
     * Free webmail is allowed but flagged for the reviewer (D-036).
     */
    private function reviewerNotes(User $user): ?string
    {
        $domain = Str::lower(Str::after((string) $user->email, '@'));

        return in_array($domain, config('blueskills.free_email_domains'), true)
            ? "Registered with a free email address ({$domain}). Check the organization's identity carefully."
            : null;
    }
}
