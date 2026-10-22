<?php

namespace App\Domain\Crm\Policies;

use App\Domain\Crm\Models\Lead;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

/**
 * CRM Agents and Superusers (`crm.manage`) follow up with leads. The phone number shows on the
 * lead page only and each view is logged (D-095).
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasStaffPermission(Permission::CrmManage->value);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->viewAny($user);
    }
}
