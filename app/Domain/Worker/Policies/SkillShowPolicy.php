<?php

namespace App\Domain\Worker\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Worker\Models\SkillShow;

class SkillShowPolicy
{
    /**
     * Module 2 serves the video only to its worker and to moderators. Employer and agency
     * access arrives with applications and talent search (Modules 3–4).
     */
    public function view(User $user, SkillShow $skillShow): bool
    {
        return $this->owns($user, $skillShow) || $this->moderate($user);
    }

    public function update(User $user, SkillShow $skillShow): bool
    {
        return $this->owns($user, $skillShow);
    }

    public function delete(User $user, SkillShow $skillShow): bool
    {
        return $this->owns($user, $skillShow);
    }

    public function moderate(User $user, ?SkillShow $skillShow = null): bool
    {
        return $user->hasStaffPermission(Permission::SkillShowsModerate->value);
    }

    private function owns(User $user, SkillShow $skillShow): bool
    {
        return $user->isWorker() && $skillShow->workerProfile()->where('user_id', $user->id)->exists();
    }
}
