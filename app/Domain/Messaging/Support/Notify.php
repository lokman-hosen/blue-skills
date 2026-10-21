<?php

namespace App\Domain\Messaging\Support;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Notifications\AppNotification;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;
use Illuminate\Support\Collection;

/**
 * Sends alerts to the right people: the worker of an application, or the members of an
 * organization who can act on it (assigned recruiter first, then everyone with the permission).
 */
final class Notify
{
    /**
     * @param  array<string, string|int>  $params
     */
    public static function worker(Application $application, NotificationEvent $event, array $params = [], ?string $url = null): void
    {
        $user = $application->workerProfile->user;

        if ($user === null) {
            return;
        }

        $user->notify(new AppNotification($event, [
            'job' => $application->job->title,
            'org' => $application->job->organization->display_name,
            ...$params,
        ], $url ?? route('worker.applications.show', $application, false)));
    }

    /**
     * Anyone, outside an application (e.g. an invitation to apply).
     *
     * @param  array<string, string|int>  $params
     */
    public static function user(User $user, NotificationEvent $event, array $params, string $url): void
    {
        $user->notify(new AppNotification($event, $params, $url));
    }

    /**
     * Members who handle the application: its assignee if set, otherwise everyone who may
     * manage applicants.
     *
     * @param  array<string, string|int>  $params
     */
    public static function handlers(Application $application, NotificationEvent $event, array $params = [], ?string $url = null): void
    {
        $organization = $application->job->organization;
        $recipients = $application->assignee !== null && OrganizationPermissions::isMember($application->assignee, $organization)
            ? collect([$application->assignee])
            : self::members($organization, Permission::ApplicationsManage);

        foreach ($recipients as $user) {
            $user->notify(new AppNotification($event, [
                'job' => $application->job->title,
                'worker' => $application->workerProfile->user->name,
                ...$params,
            ], $url ?? route('org.applicants.show', $application, false)));
        }
    }

    /**
     * The people running a mobility case: its handler while they can still manage it, otherwise
     * every member of the managing organization with `mobility.manage`.
     *
     * @param  array<string, string|int>  $params
     */
    public static function caseManagers(MobilityCase $case, NotificationEvent $event, array $params = []): void
    {
        $organization = $case->managingOrganization;

        if ($organization === null) {
            return;
        }

        $recipients = $case->handler !== null && OrganizationPermissions::allows($case->handler, $organization, Permission::MobilityManage)
            ? collect([$case->handler])
            : self::members($organization, Permission::MobilityManage);

        foreach ($recipients as $user) {
            $user->notify(new AppNotification($event, [
                'reference' => (string) $case->reference,
                'worker' => (string) $case->application->workerProfile->user?->name,
                ...$params,
            ], route('org.cases.show', $case, false)));
        }
    }

    /**
     * @return Collection<int, User>
     */
    public static function members(Organization $organization, Permission $permission): Collection
    {
        return $organization->users()->get()
            ->filter(fn (User $user) => OrganizationPermissions::allows($user, $organization, $permission))
            ->values();
    }
}
