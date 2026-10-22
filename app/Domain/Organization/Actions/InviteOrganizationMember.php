<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Billing\Support\BillingLimits;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Mail\OrganizationInvitationMail;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteOrganizationMember
{
    public const VALID_DAYS = 7;

    /**
     * Emails a single-use invitation. Only a hash of the token is stored; an earlier pending
     * invitation for the same address is replaced.
     *
     * @throws ValidationException
     */
    public function handle(Organization $organization, User $inviter, string $email, OrganizationRole $role): OrganizationInvitation
    {
        $email = Str::lower(trim($email));

        if ($role->organizationType() !== $organization->type) {
            throw ValidationException::withMessages(['role' => __('team.role_not_allowed')]);
        }

        if ($organization->users()->whereRaw('lower(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => __('team.already_member')]);
        }

        BillingLimits::ensureSeatAvailable($organization);

        $token = Str::random(48);

        $invitation = DB::transaction(function () use ($organization, $inviter, $email, $role, $token): OrganizationInvitation {
            $organization->invitations()
                ->whereRaw('lower(email) = ?', [$email])
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $invitation = $organization->invitations()->create([
                'email' => $email,
                'role' => $role,
                'token_hash' => OrganizationInvitation::hashToken($token),
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays(self::VALID_DAYS),
            ]);

            activity('organization')->performedOn($organization)->causedBy($inviter)
                ->withProperties(['email' => $email, 'role' => $role->value])
                ->log('member.invited');

            return $invitation;
        });

        Mail::to($email)->locale($inviter->locale)->queue(new OrganizationInvitationMail($invitation, $token));

        return $invitation;
    }
}
