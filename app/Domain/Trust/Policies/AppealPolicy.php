<?php

namespace App\Domain\Trust\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\AppealStatus;
use App\Domain\Trust\Models\Appeal;

/**
 * Appeals are decided by a trust officer other than the one who suspended (D-088).
 */
class AppealPolicy
{
    public function decide(User $user, Appeal $appeal): bool
    {
        return $appeal->status === AppealStatus::Submitted
            && $user->hasStaffPermission(Permission::TrustManage->value)
            && $appeal->suspension->actor_id !== $user->id;
    }
}
