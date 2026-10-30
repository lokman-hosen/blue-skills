<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Mail\StaffInvitationMail;
use App\Domain\Identity\Models\StaffInvitation;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteStaffMember
{
    /**
     * @throws ValidationException
     */
    public function handle(User $inviter, string $email, StaffRole|string $role): StaffInvitation
    {
        $role = $role instanceof StaffRole ? $role->value : $role;
        $email = Str::lower(trim($email));

        if (User::withTrashed()->whereRaw('lower(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => __('staff.email_taken')]);
        }

        $token = Str::random(48);

        $invitation = DB::transaction(function () use ($inviter, $email, $role, $token): StaffInvitation {
            StaffInvitation::query()
                ->whereRaw('lower(email) = ?', [$email])
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $invitation = StaffInvitation::query()->create([
                'email' => $email,
                'role' => $role,
                'token_hash' => StaffInvitation::hashToken($token),
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays(StaffInvitation::VALID_DAYS),
            ]);

            activity('identity')->performedOn($invitation)->causedBy($inviter)
                ->withProperties(['email' => $email, 'role' => $role])
                ->log('staff.invited');

            return $invitation;
        });

        Mail::to($email)->queue(new StaffInvitationMail($invitation, $token));

        return $invitation;
    }
}
