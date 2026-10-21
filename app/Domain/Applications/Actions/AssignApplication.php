<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Support\OrganizationPermissions;
use Illuminate\Validation\ValidationException;

/**
 * Names the recruiter handling an application ("Handled by" for the worker). Only members of
 * the posting organization can be assigned.
 */
class AssignApplication
{
    public function handle(Application $application, ?User $assignee, User $actor): void
    {
        if ($assignee !== null && ! OrganizationPermissions::isMember($assignee, $application->job->organization)) {
            throw ValidationException::withMessages(['assigned_user_id' => __('applications.assign.not_member')]);
        }

        $previous = $application->assigned_user_id;
        $application->update(['assigned_user_id' => $assignee?->id]);

        activity('applications')->performedOn($application)->causedBy($actor)
            ->withProperties(['old' => ['assigned_user_id' => $previous], 'attributes' => ['assigned_user_id' => $assignee?->id]])
            ->log('application.assigned');
    }
}
