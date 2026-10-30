<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\StaffInvitation;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

class AcceptStaffInvitation
{
    /**
     * Creates the staff account. The email is verified because the invite link was emailed.
     */
    public function handle(StaffInvitation $invitation, string $name, string $password): User
    {
        return DB::transaction(function () use ($invitation, $name, $password): User {
            $user = User::query()->create([
                'type' => UserType::Staff,
                'name' => $name,
                'email' => $invitation->email,
                'password' => $password,
                'timezone' => 'Asia/Dubai',
                'data_region' => config('blueskills.default_data_region'),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignStaffRole($invitation->role);

            $invitation->forceFill(['accepted_at' => now()])->save();

            activity('identity')->performedOn($user)->causedBy($user)
                ->withProperties(['role' => $invitation->role])
                ->log('staff.joined');

            return $user;
        });
    }
}
