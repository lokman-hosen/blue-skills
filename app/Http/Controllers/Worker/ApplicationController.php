<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Applications\Actions\AcknowledgeJobChange;
use App\Domain\Applications\Actions\DeclineInvitation;
use App\Domain\Applications\Actions\RespondToOffer;
use App\Domain\Applications\Actions\RevokeApplicationItem;
use App\Domain\Applications\Actions\WithdrawApplication;
use App\Domain\Applications\Data\JobInvitationData;
use App\Domain\Applications\Data\WorkerApplicationData;
use App\Domain\Applications\Data\WorkerApplicationListItemData;
use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\JobInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "My applications": status, next step, interviews, offers and shared items (spec §6).
 */
class ApplicationController extends Controller
{
    use ResolvesWorkerProfile;

    public function index(Request $request): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('view', $profile);

        $applications = $profile->applications()
            ->with(['job.employer', 'job.destinationCountry', 'job.costs', 'job.occupation', 'interviews'])
            ->latest('applied_at')
            ->get();

        $invitations = JobInvitation::query()
            ->where('worker_profile_id', $profile->id)
            ->where('status', InvitationStatus::Sent)
            ->with(['job.employer', 'job.destinationCountry', 'job.costs', 'job.occupation', 'organization'])
            ->latest()
            ->get();

        return Inertia::render('worker/applications/index', [
            'applications' => $applications->map(fn (Application $application) => WorkerApplicationListItemData::fromApplication($application))->values()->all(),
            'invitations' => $invitations->map(fn (JobInvitation $invitation) => JobInvitationData::fromInvitation($invitation))->values()->all(),
        ]);
    }

    public function show(Request $request, Application $application): Response
    {
        $this->workerProfile($request);
        Gate::authorize('respond', $application);

        return Inertia::render('worker/applications/show', [
            'application' => WorkerApplicationData::fromApplication($application),
        ]);
    }

    public function withdraw(Request $request, Application $application, WithdrawApplication $withdraw): RedirectResponse
    {
        Gate::authorize('respond', $application);

        $withdraw->handle($application);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('applications.withdrawn')]);

        return to_route('worker.applications.show', $application);
    }

    public function respondToOffer(Request $request, Application $application, string $decision, RespondToOffer $respond): RedirectResponse
    {
        Gate::authorize('respond', $application);

        $respond->handle($application, $decision === 'accept');
        Inertia::flash('toast', ['type' => 'success', 'message' => __($decision === 'accept' ? 'applications.offer.accepted' : 'applications.offer.declined')]);

        return to_route('worker.applications.show', $application);
    }

    public function revokeItem(Request $request, Application $application, ApplicationConsentItem $item, RevokeApplicationItem $revoke): RedirectResponse
    {
        Gate::authorize('respond', $application);

        $revoke->handle($application, $item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('applications.consent.revoked')]);

        return to_route('worker.applications.show', $application);
    }

    public function declineInvitation(Request $request, JobInvitation $invitation, DeclineInvitation $decline): RedirectResponse
    {
        Gate::authorize('respond', $invitation);

        $decline->handle($invitation);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('invitations.declined')]);

        return to_route('worker.applications.index');
    }

    public function acknowledgeJobChange(Request $request, Application $application, AcknowledgeJobChange $acknowledge): RedirectResponse
    {
        Gate::authorize('respond', $application);

        $acknowledge->handle($application);

        return to_route('worker.applications.show', $application);
    }
}
