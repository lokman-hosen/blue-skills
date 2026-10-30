<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Actions\ChangeStaffRole;
use App\Domain\Identity\Actions\DeactivateStaffMember;
use App\Domain\Identity\Actions\InviteStaffMember;
use App\Domain\Identity\Actions\ResetStaffTwoFactor;
use App\Domain\Identity\Data\StaffInvitationData;
use App\Domain\Identity\Data\StaffMemberData;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\StaffInvitation;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\StaffDirectory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(private readonly StaffDirectory $staff) {}

    public function index(Request $request): Response
    {
        Gate::authorize('manageStaff');

        return Inertia::render('admin/staff', [
            'members' => User::query()->where('type', UserType::Staff)->orderBy('name')->get()
                ->map(fn (User $member) => new StaffMemberData(
                    id: $member->id,
                    name: $member->name,
                    email: $member->email,
                    role: $this->staff->roleOf($member),
                    twoFactorEnabled: $member->two_factor_confirmed_at !== null,
                    isYou: $member->is($request->user()),
                ))
                ->values()
                ->all(),
            'invitations' => StaffInvitation::query()->with('inviter')
                ->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())
                ->latest()->get()
                ->map(fn (StaffInvitation $invitation) => StaffInvitationData::fromInvitation($invitation))
                ->values()
                ->all(),
            'roles' => StaffRole::cases(),
        ]);
    }

    public function invite(InviteStaffRequest $request, InviteStaffMember $invite): RedirectResponse
    {
        $invite->handle($request->user(), $request->string('email')->toString(), $request->enum('role', StaffRole::class));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.invited')]);

        return back();
    }

    public function revoke(Request $request, StaffInvitation $invitation): RedirectResponse
    {
        Gate::authorize('manageStaff');
        $invitation->forceFill(['revoked_at' => now()])->save();
        activity('identity')->performedOn($invitation)->causedBy($request->user())->log('staff.invitation_revoked');

        return back();
    }

    public function updateRole(UpdateStaffRoleRequest $request, User $member, ChangeStaffRole $change): RedirectResponse
    {
        abort_unless($member->isStaff(), 404);
        $change->handle($member, $request->enum('role', StaffRole::class), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.role_updated')]);

        return back();
    }

    public function deactivate(Request $request, User $member, DeactivateStaffMember $deactivate): RedirectResponse
    {
        Gate::authorize('manageStaff');
        abort_unless($member->isStaff(), 404);
        $deactivate->handle($member, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('staff.deactivated')]);

        return back();
    }

    public function resetTwoFactor(Request $request, User $member, ResetStaffTwoFactor $reset): RedirectResponse
    {
        Gate::authorize('manageStaff');
        abort_unless($member->isStaff(), 404);
        $reset->handle($member, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('staff.two_factor_reset')]);

        return back();
    }
}
