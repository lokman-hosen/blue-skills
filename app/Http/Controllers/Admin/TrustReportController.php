<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Trust\Actions\DismissReport;
use App\Domain\Trust\Data\ReportData;
use App\Domain\Trust\Data\TrustCaseListItemData;
use App\Domain\Trust\Enums\CasePriority;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\States\Report\Investigating;
use App\Domain\Trust\States\Report\Open;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DismissReportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reports from "Report this job" and "Report message" (prototype x-trust): dismiss with a
 * reason, or handle on a trust case (spec §10).
 */
class TrustReportController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TrustCase::class);
        $state = $request->string('state')->toString() === 'all' ? 'all' : 'open';

        $reports = Report::query()
            ->when($state === 'open', fn ($query) => $query->whereState('status', [Open::class, Investigating::class]))
            ->with(['reporter', 'subject', 'trustCase'])
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/trust/reports', [
            'reports' => $reports->through(fn (Report $report) => ReportData::fromReport($report)),
            'state' => $state,
            'dismissReasons' => TrustReason::forDismissal(),
            'priorities' => array_map(fn (CasePriority $priority) => $priority->value, CasePriority::cases()),
            'openCases' => TrustCase::query()->whereNotState('status', 'closed')->with(['subject', 'assignee'])->withCount('reports')->latest()->limit(50)->get()
                ->map(fn (TrustCase $case) => TrustCaseListItemData::fromCase($case))->values()->all(),
        ]);
    }

    public function dismiss(DismissReportRequest $request, Report $report, DismissReport $dismiss): RedirectResponse
    {
        Gate::authorize('viewAny', TrustCase::class);

        $dismiss->handle($report, TrustReason::from($request->string('reason')->toString()), $request->string('note')->trim()->toString() ?: null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('trust.done.dismissed')]);

        return back();
    }
}
