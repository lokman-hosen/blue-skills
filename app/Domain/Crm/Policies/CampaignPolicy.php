<?php

namespace App\Domain\Crm\Policies;

use App\Domain\Crm\Models\Campaign;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

/**
 * CRM Agents and Superusers (`crm.manage`) run campaigns and read their analytics (spec §9).
 */
class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasStaffPermission(Permission::CrmManage->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $this->viewAny($user);
    }
}
