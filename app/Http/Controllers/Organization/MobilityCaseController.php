<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Mobility\Actions\ChangeCaseStage;
use App\Domain\Mobility\Actions\CloseMobilityCase;
use App\Domain\Mobility\Actions\UpdateCaseNextAction;
use App\Domain\Mobility\Data\MobilityCaseData;
use App\Domain\Mobility\Enums\CaseClosedReason;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\ChangeCaseStageRequest;
use App\Http\Requests\Organization\CloseMobilityCaseRequest;
use App\Http\Requests\Organization\UpdateCaseNextActionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One mobility case: the managing organization runs its stages, the employer of an agency job
 * follows it (D-074, product rule 8).
 */
class MobilityCaseController extends Controller
{
    use ResolvesCurrentOrganization;

    public function show(Request $request, MobilityCase $mobilityCase): Response
    {
        $this->ownCase($request, $mobilityCase);
        Gate::authorize('view', $mobilityCase);

        return Inertia::render('org/cases/show', [
            'mobilityCase' => MobilityCaseData::fromCase($mobilityCase, forWorker: false),
            'closeReasons' => array_map(fn (CaseClosedReason $reason) => $reason->value, CaseClosedReason::cases()),
            'canManage' => Gate::allows('manage', $mobilityCase),
        ]);
    }

    public function stage(ChangeCaseStageRequest $request, MobilityCase $mobilityCase, MobilityCaseStage $stage, string $action, ChangeCaseStage $change): RedirectResponse
    {
        $this->ownCase($request, $mobilityCase);
        Gate::authorize('manage', $mobilityCase);

        $change->handle($mobilityCase, $stage, $action, $request->string('note')->trim()->toString() ?: null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("mobility.done.{$action}")]);

        return to_route('org.cases.show', $mobilityCase);
    }

    public function nextAction(UpdateCaseNextActionRequest $request, MobilityCase $mobilityCase, MobilityCaseStage $stage, UpdateCaseNextAction $update): RedirectResponse
    {
        $this->ownCase($request, $mobilityCase);
        Gate::authorize('manage', $mobilityCase);

        $update->handle($mobilityCase, $stage, $request->string('next_action')->trim()->toString() ?: null, $request->input('due_on') ?: null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('mobility.done.next_action')]);

        return to_route('org.cases.show', $mobilityCase);
    }

    public function close(CloseMobilityCaseRequest $request, MobilityCase $mobilityCase, CloseMobilityCase $close): RedirectResponse
    {
        $this->ownCase($request, $mobilityCase);
        Gate::authorize('manage', $mobilityCase);

        $close->handle($mobilityCase, $request->enum('reason', CaseClosedReason::class) ?? CaseClosedReason::Other, $request->string('note')->trim()->toString() ?: null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('mobility.done.close')]);

        return to_route('org.cases.show', $mobilityCase);
    }

    /**
     * Cases are reached through the managing or employing organization only.
     */
    private function ownCase(Request $request, MobilityCase $mobilityCase): void
    {
        $organization = $this->organization($request);
        abort_unless($organization->is($mobilityCase->managingOrganization) || $organization->is($mobilityCase->employer), 404);
    }
}
