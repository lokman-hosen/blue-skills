<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResetStaffTwoFactor
{
    /**
     * Clears a staff member's authenticator, recovery codes and passkeys after they lost their
     * phone (D-111). They sign in with their password and must set up two-factor again before
     * any staff screen opens (EnsureStaffTwoFactor).
     *
     * @throws ValidationException
     */
    public function handle(User $member, User $actor): void
    {
        if ($member->is($actor)) {
            throw ValidationException::withMessages(['member' => __('staff.cannot_reset_self')]);
        }

        $passkeys = $member->passkeys()->count();

        if ($member->two_factor_secret === null && $member->two_factor_confirmed_at === null && $passkeys === 0) {
            throw ValidationException::withMessages(['member' => __('staff.nothing_to_reset')]);
        }

        DB::transaction(function () use ($member, $actor, $passkeys): void {
            $hadTwoFactor = $member->two_factor_confirmed_at !== null;

            $member->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
            $member->passkeys()->delete();

            activity('identity')->performedOn($member)->causedBy($actor)
                ->withProperties([
                    'old' => ['two_factor' => $hadTwoFactor, 'passkeys' => $passkeys],
                    'attributes' => ['two_factor' => false, 'passkeys' => 0],
                ])
                ->log('staff.two_factor_reset');
        });
    }
}
