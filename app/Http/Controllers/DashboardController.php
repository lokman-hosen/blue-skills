<?php

namespace App\Http\Controllers;

use App\Domain\Applications\Data\ApplicantCountsData;
use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Data\CohortInviteData;
use App\Domain\Institute\Data\CohortSummaryData;
use App\Domain\Institute\Data\InstituteCountsData;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\Course;
use App\Domain\Jobs\Data\JobCountsData;
use App\Domain\Jobs\Data\JobListItemData;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Mobility\Data\CaseCountsData;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Organization\Support\OrganizationPermissions;
use App\Domain\Worker\Data\WorkerPassportData;
use App\Http\Controllers\Organization\ComplianceController;
use App\Http\Controllers\Public\CohortInviteController;
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
                'cohortInvite' => $this->pendingCohortInvite($request, $user),
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
                ? $organization->postedJobs()->with(['occupation', 'destinationCountry', 'verificationRequests', 'employer', 'mandate'])->withCount('applications')->latest('updated_at')->limit(5)->get()
                    ->map(fn (JobPosting $job) => JobListItemData::fromJob($job))->values()->all()
                : [],
            'canPostJobs' => Gate::forUser($user)->allows('create', [JobPosting::class, $organization]),
            // Employer Owners / HR Admins: agency mandates waiting for their answer (D-074).
            'mandateRequests' => OrganizationPermissions::allows($user, $organization, Permission::MandatesConfirm)
                ? $organization->employerMandates()->where('status', MandateStatus::AwaitingEmployer)->count()
                : 0,
            'applicantCounts' => Gate::forUser($user)->allows('viewAny', [Application::class, $organization])
                ? ApplicantCountsData::forOrganization($organization)
                : null,
            // Mobility cases the organization runs or employs on (D-080).
            'caseCounts' => Gate::forUser($user)->allows('viewAny', [MobilityCase::class, $organization])
                ? CaseCountsData::forOrganization($organization)
                : null,
            'licenceAlert' => $this->licenceAlert($organization),
            // Institutes (Module 7).
            'instituteCounts' => Gate::forUser($user)->allows('viewAny', [Course::class, $organization])
                ? InstituteCountsData::forInstitute($organization)
                : null,
            'recentCohorts' => Gate::forUser($user)->allows('viewAny', [Course::class, $organization])
                ? $organization->cohorts()->with(['course', 'members'])->latest('starts_on')->limit(5)->get()
                    ->map(fn (Cohort $cohort) => CohortSummaryData::fromCohort($cohort))->values()->all()
                : [],
        ]);
    }

    /**
     * Agencies: "invalid" when the licence can't back jobs, "expiring" when it ends within
     * the warning window (D-080).
     *
     * @return array{state: string, expiresOn: string|null}|null
     */
    private function licenceAlert(Organization $organization): ?array
    {
        if ($organization->type !== OrganizationType::Agency || ! $organization->isVerified()) {
            return null;
        }

        $expires = $organization->licence_expires_on;
        $state = match (true) {
            ! $organization->hasValidLicence() => 'invalid',
            $expires !== null && $expires->lte(now()->addDays(ComplianceController::LICENCE_WARNING_DAYS)) => 'expiring',
            default => null,
        };

        return $state === null ? null : ['state' => $state, 'expiresOn' => $expires?->toDateString()];
    }

    /**
     * A cohort invitation opened before signing up, until the worker answers it (D-082).
     */
    private function pendingCohortInvite(Request $request, User $user): ?CohortInviteData
    {
        $code = $request->session()->get(CohortInviteController::SESSION_KEY);
        $cohort = is_string($code) ? Cohort::query()->where('invite_code', $code)->first() : null;

        if ($cohort === null || $user->workerProfile === null) {
            return null;
        }

        $answered = $cohort->members()->where('worker_profile_id', $user->workerProfile->id)->exists();

        return $answered ? null : CohortInviteData::fromCohort($cohort);
    }

    private function hasPendingInvitation(string $email): bool
    {
        return OrganizationInvitation::query()->pendingFor($email)->exists();
    }
}
