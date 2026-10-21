<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Institute\Actions\LeaveCohort;
use App\Domain\Institute\Actions\UpdateProgressSharing;
use App\Domain\Institute\Models\CohortMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\UpdateProgressSharingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * "My institutes" on the Privacy page: the graduate decides whether an institute follows their
 * applications, or leaves it (product rule 11, D-082).
 */
class InstituteLinkController extends Controller
{
    public function sharing(UpdateProgressSharingRequest $request, CohortMember $member, UpdateProgressSharing $update): RedirectResponse
    {
        Gate::authorize('respond', $member);
        abort_unless($member->isAccepted(), 404);

        $update->handle($member, $request->boolean('shares_progress'));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('privacy.institutes.sharing_saved')]);

        return to_route('worker.privacy.show');
    }

    public function leave(CohortMember $member, LeaveCohort $leave): RedirectResponse
    {
        Gate::authorize('respond', $member);
        abort_unless($member->isAccepted(), 404);

        $leave->handle($member);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('privacy.institutes.left', ['institute' => (string) $member->cohort->organization->display_name])]);

        return to_route('worker.privacy.show');
    }
}
