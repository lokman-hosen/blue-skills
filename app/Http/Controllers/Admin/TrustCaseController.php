<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Trust\Actions\AddCaseNote;
use App\Domain\Trust\Actions\CloseTrustCase;
use App\Domain\Trust\Actions\OpenTrustCase;
use App\Domain\Trust\Actions\SetSuspension;
use App\Domain\Trust\Actions\UpdateTrustCase;
use App\Domain\Trust\Data\TrustCaseData;
use App\Domain\Trust\Data\TrustCaseListItemData;
use App\Domain\Trust\Enums\CasePriority;
use App\Domain\Trust\Enums\TrustActionType;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\Support\TrustSubject;
use App\Domain\Worker\Models\WorkerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddCaseNoteRequest;
use App\Http\Requests\Admin\CloseTrustCaseRequest;
use App\Http\Requests\Admin\OpenTrustCaseRequest;
use App\Http\Requests\Admin\TrustActionRequest;
use App\Http\Requests\Admin\UpdateTrustCaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Trust cases (spec §10): evidence, suspensions with reason codes, appeals, outcome.
 */
class TrustCaseController extends Controller
{
    public const STATES = ['active', 'mine', 'closed', 'all'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TrustCase::class);
        $state = in_array($request->string('state')->toString(), self::STATES, true) ? $request->string('state')->toString() : 'active';

        $cases = TrustCase::query()
            ->when($state === 'active', fn ($query) => $query->whereNotState('status', 'closed'))
            ->when($state === 'mine', fn ($query) => $query->whereNotState('status', 'closed')->where('assigned_to', $request->user()?->id))
            ->when($state === 'closed', fn ($query) => $query->whereState('status', 'closed'))
            ->with(['subject', 'assignee'])
            ->withCount('reports')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/trust/cases', [
            'cases' => $cases->through(fn (TrustCase $case) => TrustCaseListItemData::fromCase($case)),
            'state' => $state,
        ]);
    }

    public function store(OpenTrustCaseRequest $request, OpenTrustCase $open): RedirectResponse
    {
        Gate::authorize('create', TrustCase::class);

        $report = Report::query()->with('subject')->findOrFail($request->string('report_id')->toString());
        $subject = TrustSubject::fromReport($report);

        if ($subject === null) {
            throw ValidationException::withMessages(['report_id' => __('trust.errors.no_subject')]);
        }

        // A report about something that already has an active case joins that case.
        $existing = TrustCase::query()->whereNotState('status', 'closed')
            ->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->getKey())->first();

        if ($existing !== null) {
            OpenTrustCase::attach($report, $existing);
            Inertia::flash('toast', ['type' => 'success', 'message' => __('trust.done.attached', ['reference' => $existing->reference])]);

            return to_route('admin.trust.cases.show', $existing);
        }

        $case = $open->handle($subject, $report->category, CasePriority::from($request->string('priority')->toString()), $request->string('summary')->trim()->toString(), [$report], $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('trust.done.opened', ['reference' => $case->reference])]);

        return to_route('admin.trust.cases.show', $case);
    }

    public function show(TrustCase $case): Response
    {
        Gate::authorize('view', $case);

        return Inertia::render('admin/trust/case', [
            'trustCase' => TrustCaseData::fromCase($case),
            'officers' => User::query()->where('type', UserType::Staff)->orderBy('name')->get()
                ->filter(fn (User $user) => $user->hasStaffPermission(Permission::TrustManage->value))
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])->values()->all(),
            'actionReasons' => TrustReason::forActions(),
            'restoreReasons' => [TrustReason::NotABreach, TrustReason::Resolved, TrustReason::NoEvidence],
            'closeReasons' => TrustReason::cases(),
            'priorities' => array_map(fn (CasePriority $priority) => $priority->value, CasePriority::cases()),
            'subjectSuspended' => $case->subject !== null && TrustSubject::isSuspended($case->subject),
        ]);
    }

    public function update(UpdateTrustCaseRequest $request, TrustCase $case, UpdateTrustCase $update): RedirectResponse
    {
        Gate::authorize('update', $case);

        $update->handle($case, $request->string('assigned_to')->toString() ?: null, CasePriority::from($request->string('priority')->toString()), $request->boolean('investigate'), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('trust.done.updated')]);

        return to_route('admin.trust.cases.show', $case);
    }

    public function note(AddCaseNoteRequest $request, TrustCase $case, AddCaseNote $add): RedirectResponse
    {
        Gate::authorize('update', $case);

        /** @var list<UploadedFile> $files */
        $files = array_values(array_filter((array) $request->file('files', []), fn ($file) => $file instanceof UploadedFile));
        $add->handle($case, $request->string('body')->trim()->toString(), $files, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('trust.done.note')]);

        return to_route('admin.trust.cases.show', $case);
    }

    public function action(TrustActionRequest $request, TrustCase $case, string $action, SetSuspension $suspension): RedirectResponse
    {
        Gate::authorize('update', $case);
        $subject = $case->subject;
        abort_unless($subject instanceof Organization || $subject instanceof JobPosting || $subject instanceof WorkerProfile, 404);

        $suspension->handle($subject, TrustActionType::from($action), TrustReason::from($request->string('reason')->toString()), $request->string('note')->trim()->toString(), $request->user(), $case);
        Inertia::flash('toast', ['type' => 'success', 'message' => __("trust.done.{$action}")]);

        return to_route('admin.trust.cases.show', $case);
    }

    public function close(CloseTrustCaseRequest $request, TrustCase $case, CloseTrustCase $close): RedirectResponse
    {
        Gate::authorize('update', $case);

        $close->handle($case, TrustReason::from($request->string('reason')->toString()), $request->string('note')->trim()->toString() ?: null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('trust.done.closed')]);

        return to_route('admin.trust.cases.show', $case);
    }
}
