<?php

namespace App\Domain\Trust\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Trust\Models\TrustCase;

/**
 * Trust Officers and Superusers (`trust.manage`) triage reports and run cases (spec §10).
 */
class TrustCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasStaffPermission(Permission::TrustManage->value);
    }

    public function view(User $user, TrustCase $case): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, TrustCase $case): bool
    {
        return $this->viewAny($user);
    }
}
