<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Institute\Data\CohortMemberData;
use App\Domain\Institute\Data\CohortSummaryData;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\Course;
use App\Domain\Organization\Support\OrganizationPermissions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Graduates across all cohorts with their training claims and assessments (prototype i-grads).
 */
class GraduateController extends Controller
{
    use ResolvesCurrentOrganization;

    public const STATES = ['joined', 'waiting', 'all'];

    public function index(Request $request): Response
    {
        $institute = $this->organization($request);
        Gate::authorize('viewAny', [Course::class, $institute]);

        $cohorts = $institute->cohorts()->with(['course', 'members'])->latest('starts_on')->get();
        $cohort = $cohorts->firstWhere('id', $request->string('cohort')->toString());
        $state = in_array($request->string('state')->toString(), self::STATES, true) ? $request->string('state')->toString() : 'joined';

        $members = CohortMember::query()
            ->whereIn('cohort_id', $cohort !== null ? [$cohort->id] : $cohorts->modelKeys())
            ->when($state === 'joined', fn ($query) => $query->where('status', CohortMemberStatus::Accepted))
            ->when($state === 'waiting', fn ($query) => $query->where('status', CohortMemberStatus::Invited))
            ->with(['cohort.course', 'workerProfile.user', 'workerProfile.skillShows', 'attestations', 'assessments'])
            ->latest('responded_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('org/graduates', [
            'graduates' => $members->through(fn (CohortMember $member) => CohortMemberData::fromMember($member)),
            'cohorts' => $cohorts->map(fn (Cohort $item) => CohortSummaryData::fromCohort($item))->values()->all(),
            'filters' => ['cohort' => $cohort?->id, 'state' => $state],
            'abilities' => [
                'manage' => false,
                'invite' => OrganizationPermissions::allows($request->user(), $institute, Permission::GraduatesInvite),
                'attest' => $institute->isVerified() && OrganizationPermissions::allows($request->user(), $institute, Permission::AttestationsIssue),
                'assess' => $institute->isVerified() && OrganizationPermissions::allows($request->user(), $institute, Permission::AssessmentsRecord),
            ],
            'instituteVerified' => $institute->isVerified(),
        ]);
    }
}
