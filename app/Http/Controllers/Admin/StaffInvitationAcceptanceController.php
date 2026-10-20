<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Actions\AcceptStaffInvitation;
use App\Domain\Identity\Data\StaffInvitationData;
use App\Domain\Identity\Models\StaffInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcceptStaffInvitationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * New staff set their name and password, then go straight to 2FA setup (D-035).
 */
class StaffInvitationAcceptanceController extends Controller
{
    public function show(string $token): Response
    {
        $invitation = StaffInvitation::findPendingByToken($token);

        return Inertia::render('onboarding/staff-invitation', [
            'token' => $token,
            'invitation' => $invitation !== null ? StaffInvitationData::fromInvitation($invitation->load('inviter')) : null,
        ]);
    }

    public function store(AcceptStaffInvitationRequest $request, string $token, AcceptStaffInvitation $accept): RedirectResponse
    {
        $invitation = StaffInvitation::findPendingByToken($token);
        abort_if($invitation === null, 410, __('team.invalid_title'));

        $user = $accept->handle($invitation, $request->string('name')->trim()->toString(), $request->string('password')->toString());

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->passwordConfirmed();
        Inertia::flash('toast', ['type' => 'info', 'message' => __('staff.set_up_two_factor')]);

        return to_route('security.edit');
    }
}
