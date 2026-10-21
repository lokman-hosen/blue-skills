<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Institute\Actions\AddRosterLines;
use App\Domain\Institute\Actions\ChangeCohortStatus;
use App\Domain\Institute\Actions\RemoveRosterLine;
use App\Domain\Institute\Actions\SaveCohort;
use App\Domain\Institute\Data\CohortData;
use App\Domain\Institute\Data\CohortSummaryData;
use App\Domain\Institute\Enums\CohortStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Support\CohortInvite;
use App\Domain\Organization\Support\OrganizationPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\AddRosterRequest;
use App\Http\Requests\Organization\SaveCohortRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Cohorts: who joined, and the invite link, QR code and WhatsApp text the institute shares
 * itself (D-082). The roster only tracks who accepted.
 */
class CohortController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $institute = $this->organization($request);
        Gate::authorize('viewAny', [Course::class, $institute]);

        $cohorts = $institute->cohorts()->with(['course', 'members'])->latest('starts_on')->get();

        return Inertia::render('org/cohorts/index', [
            'cohorts' => $cohorts->map(fn (Cohort $cohort) => CohortSummaryData::fromCohort($cohort))->values()->all(),
        ]);
    }

    public function store(SaveCohortRequest $request, Course $course, SaveCohort $save): RedirectResponse
    {
        $institute = $this->organization($request);
        abort_unless($course->organization_id === $institute->id, 404);
        Gate::authorize('create', new Cohort(['organization_id' => $institute->id]));

        $cohort = $save->handle($course, null, $this->fields($request), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('cohorts.created')]);

        return to_route('org.cohorts.show', $cohort);
    }

    public function show(Request $request, Cohort $cohort): Response
    {
        $this->ownCohort($request, $cohort);
        Gate::authorize('view', $cohort);

        return Inertia::render('org/cohorts/show', [
            'cohort' => CohortData::fromCohort($cohort),
            'abilities' => [
                'manage' => Gate::allows('update', $cohort),
                'invite' => Gate::allows('invite', $cohort),
                // Per graduate the policy also needs them to have accepted (product rule 11).
                'attest' => $cohort->organization->isVerified() && OrganizationPermissions::allows($request->user(), $cohort->organization, Permission::AttestationsIssue),
                'assess' => $cohort->organization->isVerified() && OrganizationPermissions::allows($request->user(), $cohort->organization, Permission::AssessmentsRecord),
            ],
            'instituteVerified' => $cohort->organization->isVerified(),
        ]);
    }

    public function update(SaveCohortRequest $request, Cohort $cohort, SaveCohort $save): RedirectResponse
    {
        $this->ownCohort($request, $cohort);
        Gate::authorize('update', $cohort);

        $save->handle($cohort->course, $cohort, $this->fields($request), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('cohorts.saved')]);

        return to_route('org.cohorts.show', $cohort);
    }

    public function status(Request $request, Cohort $cohort, string $status, ChangeCohortStatus $change): RedirectResponse
    {
        $this->ownCohort($request, $cohort);
        Gate::authorize('update', $cohort);

        $change->handle($cohort, CohortStatus::from($status), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("cohorts.status_done.{$status}")]);

        return to_route('org.cohorts.show', $cohort);
    }

    public function roster(AddRosterRequest $request, Cohort $cohort, AddRosterLines $add): RedirectResponse
    {
        $this->ownCohort($request, $cohort);
        Gate::authorize('invite', $cohort);

        $added = $add->handle($cohort, $request->string('roster')->toString(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice('cohorts.roster.added', $added, ['count' => $added])]);

        return to_route('org.cohorts.show', $cohort);
    }

    public function removeMember(Request $request, Cohort $cohort, CohortMember $member, RemoveRosterLine $remove): RedirectResponse
    {
        $this->ownCohort($request, $cohort);
        Gate::authorize('invite', $cohort);

        $remove->handle($member, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('cohorts.roster.removed')]);

        return to_route('org.cohorts.show', $cohort);
    }

    /**
     * The QR code as a file for posters and classroom slides.
     */
    public function qr(Request $request, Cohort $cohort): HttpResponse
    {
        $this->ownCohort($request, $cohort);
        Gate::authorize('view', $cohort);

        return response('<?xml version="1.0" encoding="UTF-8"?>'."\n".CohortInvite::qrSvg($cohort, 1024), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="blueskills-'.strtolower($cohort->invite_code).'.svg"',
        ]);
    }

    private function ownCohort(Request $request, Cohort $cohort): void
    {
        abort_unless($cohort->organization_id === $this->organization($request)->id, 404);
    }

    /**
     * @return array{name: string, starts_on: string|null, ends_on: string|null}
     */
    private function fields(SaveCohortRequest $request): array
    {
        return [
            'name' => $request->string('name')->trim()->toString(),
            'starts_on' => $request->string('starts_on')->toString() ?: null,
            'ends_on' => $request->string('ends_on')->toString() ?: null,
        ];
    }
}
