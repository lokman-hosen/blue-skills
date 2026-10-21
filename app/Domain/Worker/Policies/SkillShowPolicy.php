<?php

namespace App\Domain\Worker\Policies;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Support\OrganizationPermissions;
use App\Domain\Worker\Models\SkillShow;

class SkillShowPolicy
{
    /**
     * The worker, moderators, and organizations that can see an application the approved video
     * is still shared with (product rules 3 and 6).
     */
    public function view(User $user, SkillShow $skillShow): bool
    {
        return $this->owns($user, $skillShow) || $this->moderate($user) || $this->sharedWithApplication($user, $skillShow);
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

    private function sharedWithApplication(User $user, SkillShow $skillShow): bool
    {
        if ($user->isWorker() || ! $skillShow->isPublished()) {
            return false;
        }

        return Application::query()
            ->where('skill_show_id', $skillShow->id)
            ->whereHas('consents', fn ($query) => $query->active()->where('item', ApplicationConsentItem::SkillShow))
            ->with('job.organization')
            ->get()
            ->contains(fn (Application $application) => OrganizationPermissions::allows($user, $application->job->organization, Permission::ApplicationsView));
    }
}
