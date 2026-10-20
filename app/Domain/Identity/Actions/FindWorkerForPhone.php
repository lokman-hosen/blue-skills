<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\PhoneSignInBlocked;
use App\Domain\Identity\Models\User;

class FindWorkerForPhone
{
    /**
     * The worker who owns a verified phone, or null when the number is new.
     *
     * @throws PhoneSignInBlocked
     */
    public function handle(string $phone): ?User
    {
        $user = User::withTrashed()->where('phone', $phone)->first();

        if ($user === null) {
            return null;
        }

        if ($user->trashed() || ! $user->isWorker()) {
            throw new PhoneSignInBlocked;
        }

        return $user;
    }
}
