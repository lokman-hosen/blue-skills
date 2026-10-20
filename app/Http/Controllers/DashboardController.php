<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Data\JobCountsData;
use App\Domain\Jobs\Data\JobListItemData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Worker\Data\WorkerPassportData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lands each user type in its own workspace. The admin overview is a placeholder until Module 8.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, CurrentOrganization $currentOrganization): Response|RedirectResponse
    {
        $user = $request->user();

        return match ($user?->type) {
            UserType::Worker => Inertia::render('worker/home', [
                'passport' => $user->workerProfile !== null ? WorkerPassportData::fromProfile($user->workerProfile) : null,
            ]),
            UserType::Staff => Inertia::render('admin/overview'),
            // Organization users without a workspace accept an invitation or register one first.
            default => match (true) {
                $user === null => Inertia::render('org/dashboard'),
                ($organization = $currentOrganization->resolve($user, $request->session())) !== null => $this->organizationDashboard($user, $organization),
                $this->hasPendingInvitation((string) $user->email) => to_route('invitations.index'),
                default => to_route('organizations.create'),
            },
        };
    }

    private function organizationDashboard(User $user, Organization $organization): Response
    {
        $canSeeJobs = Gate::forUser($user)->allows('viewAny', [JobPosting::class, $organization]);

        return Inertia::render('org/dashboard', [
            'counts' => $canSeeJobs ? JobCountsData::forOrganization($organization) : null,
            'recentJobs' => $canSeeJobs
                ? $organization->postedJobs()->with(['occupation', 'destinationCountry', 'verificationRequests'])->latest('updated_at')->limit(5)->get()
                    ->map(fn (JobPosting $job) => JobListItemData::fromJob($job))->values()->all()
                : [],
            'canPostJobs' => Gate::forUser($user)->allows('create', [JobPosting::class, $organization]),
        ]);
    }

    private function hasPendingInvitation(string $email): bool
    {
        return OrganizationInvitation::query()->pendingFor($email)->exists();
    }
}
