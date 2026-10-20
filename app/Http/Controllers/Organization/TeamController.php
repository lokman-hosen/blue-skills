<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Organization\Actions\ChangeMemberRole;
use App\Domain\Organization\Actions\InviteOrganizationMember;
use App\Domain\Organization\Actions\RemoveOrganizationMember;
use App\Domain\Organization\Actions\RevokeOrganizationInvitation;
use App\Domain\Organization\Data\TeamInvitationData;
use App\Domain\Organization\Data\TeamMemberData;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Organization\Support\OrganizationRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\InviteMemberRequest;
use App\Http\Requests\Organization\UpdateMemberRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $currentOrganization,
        private readonly OrganizationRoles $roles,
    ) {}

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewTeam', $organization);
        $user = $request->user();

        $members = $organization->members()->with('user')->orderBy('joined_at')->get();

        return Inertia::render('org/team', [
            'members' => $members->map(fn (OrganizationMember $member) => new TeamMemberData(
                id: $member->id,
                name: $member->user->name,
                email: $member->user->email,
                title: $member->title,
                role: $this->roles->roleOf($member->user, $organization),
                joinedAt: $member->joined_at?->toIso8601String(),
                isYou: $member->user_id === $user?->id,
            ))->values()->all(),
            'invitations' => $organization->invitations()
                ->with(['organization', 'inviter'])
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get()
                ->map(fn (OrganizationInvitation $invitation) => TeamInvitationData::fromInvitation($invitation))
                ->values()
                ->all(),
            'roles' => OrganizationRole::forType($organization->type),
            'canManage' => $user?->can('manageTeam', $organization) ?? false,
        ]);
    }

    public function invite(InviteMemberRequest $request, InviteOrganizationMember $invite): RedirectResponse
    {
        $organization = $this->organization($request);
        Gate::authorize('manageTeam', $organization);

        $invite->handle($organization, $request->user(), $request->string('email')->toString(), $request->enum('role', OrganizationRole::class));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.invited')]);

        return back();
    }

    public function revoke(Request $request, OrganizationInvitation $invitation, RevokeOrganizationInvitation $revoke): RedirectResponse
    {
        abort_unless($invitation->organization_id === $this->organization($request)->id, 404);
        Gate::authorize('revoke', $invitation);

        $revoke->handle($invitation, $request->user());

        return back();
    }

    public function updateRole(UpdateMemberRoleRequest $request, OrganizationMember $member, ChangeMemberRole $change): RedirectResponse
    {
        $organization = $this->memberOrganization($request, $member);
        Gate::authorize('manageTeam', $organization);

        $change->handle($member, $request->enum('role', OrganizationRole::class), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.role_updated')]);

        return back();
    }

    public function remove(Request $request, OrganizationMember $member, RemoveOrganizationMember $remove): RedirectResponse
    {
        $organization = $this->memberOrganization($request, $member);
        Gate::authorize('manageTeam', $organization);

        $remove->handle($member, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('team.removed')]);

        return back();
    }

    private function organization(Request $request): Organization
    {
        $organization = $this->currentOrganization->resolve($request->user(), $request->session());
        abort_if($organization === null, 404);

        return $organization;
    }

    private function memberOrganization(Request $request, OrganizationMember $member): Organization
    {
        $organization = $this->organization($request);
        abort_unless($member->organization_id === $organization->id, 404);

        return $organization;
    }
}
