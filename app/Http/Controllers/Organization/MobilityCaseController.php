<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Mobility\Actions\ChangeCaseStage;
use App\Domain\Mobility\Actions\CloseMobilityCase;
use App\Domain\Mobility\Actions\UpdateCaseNextAction;
use App\Domain\Mobility\Data\CaseCountsData;
use App\Domain\Mobility\Data\CaseDocumentData;
use App\Domain\Mobility\Data\CaseListItemData;
use App\Domain\Mobility\Data\MobilityCaseData;
use App\Domain\Mobility\Enums\CaseClosedReason;
use App\Domain\Mobility\Enums\CaseDocumentReturnReason;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseStage;
use App\Domain\Mobility\States\MobilityCase\Open;
use App\Domain\Mobility\Support\CaseQuery;
use App\Domain\Verification\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\ChangeCaseStageRequest;
use App\Http\Requests\Organization\CloseMobilityCaseRequest;
use App\Http\Requests\Organization\StoreCaseDocumentRequest;
use App\Http\Requests\Organization\UpdateCaseNextActionRequest;
use App\Http\Requests\Worker\StoreIdentityDocumentRequest;
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

    /**
     * Every case the organization runs or employs on (prototype a-cases), newest first, narrowed
     * by state, what needs attention or a worker's name or case reference.
     */
    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewAny', [MobilityCase::class, $organization]);

        $state = in_array($request->string('state')->toString(), ['closed', 'all'], true) ? $request->string('state')->toString() : 'open';
        $attention = in_array($request->string('attention')->toString(), CaseQuery::ATTENTION, true) ? $request->string('attention')->toString() : null;
        $search = $request->string('q')->trim()->limit(80, '')->toString();

        $query = CaseQuery::for($organization)
            ->when($state === 'open', fn ($query) => $query->whereState('status', Open::class))
            ->when($state === 'closed', fn ($query) => $query->whereNotState('status', Open::class))
            ->when($attention !== null, fn ($query) => CaseQuery::needing($query, (string) $attention))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('reference', 'ilike', "%{$search}%")
                ->orWhereHas('application.workerProfile.user', fn ($users) => $users->where('name', 'ilike', "%{$search}%"))));

        $cases = $query
            ->with(['application.job', 'application.workerProfile.user', 'stages', 'documents', 'managingOrganization'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('org/cases/index', [
            'cases' => $cases->through(fn (MobilityCase $case) => CaseListItemData::fromCase($case, viewOnly: ! $organization->is($case->managingOrganization))),
            'counts' => CaseCountsData::forOrganization($organization),
            'filters' => ['state' => $state, 'attention' => $attention, 'q' => $search],
        ]);
    }

    public function show(Request $request, MobilityCase $mobilityCase): Response
    {
        $this->ownCase($request, $mobilityCase);
        Gate::authorize('view', $mobilityCase);

        return Inertia::render('org/cases/show', [
            'mobilityCase' => MobilityCaseData::fromCase($mobilityCase, forWorker: false),
            'closeReasons' => array_map(fn (CaseClosedReason $reason) => $reason->value, CaseClosedReason::cases()),
            'canManage' => Gate::allows('manage', $mobilityCase),
            'documents' => CaseDocumentData::forViewer(
                $mobilityCase,
                $this->organization($request)->is($mobilityCase->managingOrganization) ? CaseDocumentData::VIEWER_MANAGER : CaseDocumentData::VIEWER_EMPLOYER,
            ),
            'documentTypes' => array_values(array_map(
                fn (DocumentType $type) => $type->value,
                array_filter(DocumentType::cases(), fn (DocumentType $type) => ! in_array($type, StoreCaseDocumentRequest::EXCLUDED_TYPES, true)),
            )),
            'returnReasons' => array_map(fn (CaseDocumentReturnReason $reason) => $reason->value, CaseDocumentReturnReason::cases()),
            'maxSizeMb' => StoreIdentityDocumentRequest::MAX_KB / 1024,
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
