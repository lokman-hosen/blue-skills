<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Trust\Actions\SubmitAppeal;
use App\Domain\Trust\Data\TrustActionData;
use App\Domain\Trust\Models\TrustAction;
use App\Domain\Trust\Support\Appealable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\SubmitAppealRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Suspensions the signed-in worker or organization member can see and appeal (spec §10,
 * D-088). Staff notes and names stay with staff.
 */
class AppealController extends Controller
{
    public function index(Request $request, CurrentOrganization $current): Response
    {
        $user = $request->user();
        abort_if($user === null || $user->isStaff(), 404);
        $organization = $user->isWorker() ? null : $current->resolve($user, $request->session());

        return Inertia::render('appeals/index', [
            'suspensions' => Appealable::for($user, $organization)->map(fn (TrustAction $action) => TrustActionData::fromAction($action, forStaff: false))->values()->all(),
        ]);
    }

    public function store(SubmitAppealRequest $request, TrustAction $action, CurrentOrganization $current, SubmitAppeal $submit): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);
        $organization = $user->isWorker() ? null : $current->resolve($user, $request->session());
        abort_unless(Appealable::for($user, $organization)->contains(fn (TrustAction $appealable) => $appealable->is($action)), 403);

        $submit->handle($action, $user, $request->string('statement')->trim()->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('appeals.submitted')]);

        return to_route('appeals.index');
    }
}
