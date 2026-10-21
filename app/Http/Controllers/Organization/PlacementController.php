<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Institute\Data\PlacementData;
use App\Domain\Institute\Models\CohortMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Employer interest and placements for graduates who share their progress with the institute
 * (spec §2 institute journey, D-082). Graduates who don't share it aren't listed at all.
 */
class PlacementController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $institute = $this->organization($request);
        Gate::authorize('viewPlacements', $institute);

        $members = CohortMember::query()->accepted()
            ->where('shares_progress', true)
            ->whereHas('cohort', fn ($cohorts) => $cohorts->where('organization_id', $institute->id))
            ->with([
                'cohort.course',
                'workerProfile.user',
                'workerProfile.applications' => fn ($query) => $query->with(['job.employer', 'job.organization', 'mobilityCase'])->latest('applied_at'),
                'workerProfile.jobInvitations' => fn ($query) => $query->with(['job', 'organization'])->latest(),
            ])
            ->latest('responded_at')
            ->get();

        $placements = $members->map(fn (CohortMember $member) => PlacementData::fromMember($member))->values();

        return Inertia::render('org/placements', [
            'placements' => $placements->all(),
            'counts' => [
                'sharing' => $placements->count(),
                'interest' => $placements->sum(fn (PlacementData $row) => count($row->interest)),
                'applied' => $placements->filter(fn (PlacementData $row) => $row->applications !== [])->count(),
                'placed' => $placements->filter(fn (PlacementData $row) => $row->placed)->count(),
            ],
        ]);
    }
}
