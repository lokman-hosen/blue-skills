<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Organization\Actions\IssuePilotInvite;
use App\Domain\Organization\Actions\RevokePilotInvite;
use App\Domain\Organization\Data\PilotInviteData;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\PilotInvite;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IssuePilotInviteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pilot invite codes for selected partners (D-108), managed by the staff who review
 * organization registrations.
 */
class PilotInviteController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('review', Organization::class);

        return Inertia::render('admin/pilot', [
            'pilotEnabled' => (bool) config('blueskills.pilot.enabled'),
            'invites' => PilotInvite::query()->with(['organizations', 'creator'])->latest()->limit(200)->get()
                ->map(fn (PilotInvite $invite) => PilotInviteData::fromInvite($invite))
                ->values()
                ->all(),
            'types' => OrganizationType::cases(),
        ]);
    }

    public function store(IssuePilotInviteRequest $request, IssuePilotInvite $issue): RedirectResponse
    {
        $invite = $issue->handle(
            $request->string('partner_name')->trim()->toString(),
            $request->filled('organization_type') ? OrganizationType::from($request->string('organization_type')->toString()) : null,
            $request->integer('max_uses'),
            $request->filled('expires_on') ? $request->date('expires_on') : null,
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pilot.issued', ['code' => $invite->code])]);

        return to_route('admin.pilot.index');
    }

    public function revoke(Request $request, PilotInvite $invite, RevokePilotInvite $revoke): RedirectResponse
    {
        Gate::authorize('review', Organization::class);

        $revoke->handle($invite, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('pilot.revoked')]);

        return to_route('admin.pilot.index');
    }
}
