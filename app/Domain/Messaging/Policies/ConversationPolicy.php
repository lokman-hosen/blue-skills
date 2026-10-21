<?php

namespace App\Domain\Messaging\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * Only the application's worker and the posting organization's members take part (spec §9).
 * Members who can see applications read; members who handle applicants or interviews write.
 */
class ConversationPolicy
{
    /**
     * The organization's inbox.
     */
    public function viewAny(User $user, Organization $organization): bool
    {
        return OrganizationPermissions::allows($user, $organization, Permission::ApplicationsView);
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->isWorker($user, $conversation)
            || (! $user->isWorker() && OrganizationPermissions::allows($user, $conversation->organization, Permission::ApplicationsView));
    }

    public function post(User $user, Conversation $conversation): bool
    {
        if (! $conversation->isWritable()) {
            return false;
        }

        return $this->isWorker($user, $conversation)
            || (! $user->isWorker() && (
                OrganizationPermissions::allows($user, $conversation->organization, Permission::ApplicationsManage)
                || OrganizationPermissions::allows($user, $conversation->organization, Permission::InterviewsManage)
            ));
    }

    private function isWorker(User $user, Conversation $conversation): bool
    {
        return $user->isWorker() && $conversation->workerProfile->user_id === $user->id;
    }
}
