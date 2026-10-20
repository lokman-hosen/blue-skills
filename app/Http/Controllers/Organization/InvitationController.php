<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Organization\Actions\AcceptOrganizationInvitation;
use App\Domain\Organization\Actions\SwitchCurrentOrganization;
use App\Domain\Organization\Data\TeamInvitationData;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Invitation links from email, and the pending invitations of the signed-in user.
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = OrganizationInvitation::findPendingByToken($token);

        if ($request->user() !== null && $invitation !== null) {
            return to_route('invitations.index');
        }

        if ($invitation !== null) {
            // After signing in or creating an account, come back to the pending list.
            $request->session()->put('url.intended', route('invitations.index'));
        }

        return Inertia::render('onboarding/invitation', [
            'invitation' => $invitation !== null ? TeamInvitationData::fromInvitation($invitation->load(['organization', 'inviter'])) : null,
        ]);
    }

    public function index(Request $request): Response
    {
        $email = (string) $request->user()?->email;

        return Inertia::render('onboarding/invitations', [
            'invitations' => OrganizationInvitation::query()
                ->with(['organization', 'inviter'])
                ->pendingFor($email)
                ->latest()
                ->get()
                ->map(fn (OrganizationInvitation $invitation) => TeamInvitationData::fromInvitation($invitation))
                ->values()
                ->all(),
        ]);
    }

    public function accept(Request $request, OrganizationInvitation $invitation, AcceptOrganizationInvitation $accept, SwitchCurrentOrganization $switch): RedirectResponse
    {
        Gate::authorize('accept', $invitation);

        $organization = $accept->handle($invitation, $request->user());
        $switch->handle($request->session(), $organization);

        return to_route('dashboard');
    }
}
