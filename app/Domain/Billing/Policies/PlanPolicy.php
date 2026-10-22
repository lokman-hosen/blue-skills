<?php

namespace App\Domain\Billing\Policies;

use App\Domain\Billing\Models\Plan;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

/**
 * Finance Officers and Superusers (`finance.manage`) set up plans (D-095).
 */
class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasStaffPermission(Permission::FinanceManage->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Plan $plan): bool
    {
        return $this->viewAny($user);
    }
}
