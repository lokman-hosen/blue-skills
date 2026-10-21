<?php

namespace App\Domain\Trust\Support;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Suspended as JobSuspended;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;
use App\Domain\Trust\Actions\SetSuspension;
use App\Domain\Trust\Enums\TrustActionType;
use App\Domain\Trust\Models\TrustAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The suspensions a user can see and appeal (D-088): their own worker account, or their
 * organization and its jobs suspended on their own. Organization appeals come from members
 * who may change the organization or its jobs.
 */
final class Appealable
{
    /**
     * @return Collection<int, TrustAction>
     */
    public static function for(User $user, ?Organization $organization): Collection
    {
        $subjects = [];

        if ($user->isWorker() && $user->workerProfile?->isSuspended()) {
            $subjects[] = $user->workerProfile;
        }

        if ($organization !== null && self::canAppealFor($user, $organization)) {
            if (TrustSubject::isSuspended($organization)) {
                $subjects[] = $organization;
            }

            $organization->postedJobs()->whereState('status', JobSuspended::class)->get()
                ->filter(fn (JobPosting $job) => SetSuspension::suspendedOnItsOwn($job))
                ->each(function (JobPosting $job) use (&$subjects): void {
                    $subjects[] = $job;
                });
        }

        return collect($subjects)
            ->map(fn (Model $subject) => self::latestSuspension($subject))
            ->filter()
            ->values();
    }

    public static function canAppealFor(User $user, Organization $organization): bool
    {
        return OrganizationPermissions::allows($user, $organization, Permission::OrganizationUpdate)
            || OrganizationPermissions::allows($user, $organization, Permission::JobsManage);
    }

    public static function latestSuspension(Model $subject): ?TrustAction
    {
        /** @var TrustAction|null $action */
        $action = TrustAction::query()
            ->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->getKey())
            ->latest()->orderByDesc('id')
            ->with(['subject', 'appeals'])
            ->first();

        return $action?->action === TrustActionType::Suspend ? $action : null;
    }
}
