<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Applications\Actions\AssignApplication;
use App\Domain\Applications\Actions\MoveApplications;
use App\Domain\Applications\Actions\RecordInterviewOutcome;
use App\Domain\Applications\Actions\RejectApplication;
use App\Domain\Applications\Actions\RejectApplications;
use App\Domain\Applications\Actions\ScheduleInterview;
use App\Domain\Applications\Actions\SendOffer;
use App\Domain\Applications\Data\ApplicantData;
use App\Domain\Applications\Data\ApplicantListItemData;
use App\Domain\Applications\Data\MemberOptionData;
use App\Domain\Applications\Enums\InterviewMode;
use App\Domain\Applications\Enums\InterviewOutcome;
use App\Domain\Applications\Enums\RejectionReason;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\Support\ApplicationStages;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\AssignApplicantRequest;
use App\Http\Requests\Organization\InterviewOutcomeRequest;
use App\Http\Requests\Organization\MoveApplicantsRequest;
use App\Http\Requests\Organization\RejectApplicantRequest;
use App\Http\Requests\Organization\RejectApplicantsRequest;
use App\Http\Requests\Organization\ScheduleInterviewRequest;
use App\Http\Requests\Organization\SendOfferRequest;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A job's applicants and pipeline: stage moves (one or many), rejection with a reason,
 * recruiter assignment, interviews and offers (spec §6, deck slide 18). Applicants are only
 * reached through the current organization's jobs.
 */
class ApplicantController extends Controller
{
    /** Pipeline filters: every stage, plus the end states. */
    public const FILTERS = [...ApplicationStages::ORDER, 'rejected', 'withdrawn', 'declined'];

    use ResolvesCurrentOrganization;

    public function index(Request $request, JobPosting $job): Response
    {
        $organization = $this->ownJob($request, $job);
        Gate::authorize('viewAny', [Application::class, $organization]);

        $stage = in_array($request->string('stage')->toString(), self::FILTERS, true) ? $request->string('stage')->toString() : null;

        /** @var array<string, int> $counts */
        $counts = $job->applications()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        $applicants = $job->applications()
            ->with(['snapshot', 'consents', 'assignee', 'interviews', 'workerProfile.user'])
            ->when($stage !== null, fn ($query) => $query->where('status', $stage))
            ->oldest('applied_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('org/jobs/applicants', [
            'job' => ['code' => $job->public_code, 'title' => $job->title, 'headcount' => $job->headcount],
            'applicants' => $applicants->through(fn (Application $application) => ApplicantListItemData::fromApplication($application)),
            'counts' => $counts,
            'stage' => $stage,
            'filters' => self::FILTERS,
            'movable' => ApplicationStages::MOVABLE,
            'canManage' => OrganizationPermissions::allows($request->user(), $organization, Permission::ApplicationsManage),
        ]);
    }

    public function show(Request $request, Application $application): Response
    {
        $organization = $this->ownApplication($request, $application);
        Gate::authorize('view', $application);

        $canManage = Gate::allows('manage', $application);

        return Inertia::render('org/applicants/show', [
            'applicant' => ApplicantData::fromApplication($application),
            'abilities' => [
                'manage' => $canManage,
                'interview' => Gate::allows('scheduleInterview', $application),
            ],
            'members' => $canManage
                ? $organization->users()->orderBy('name')->get()
                    ->map(fn (User $user) => new MemberOptionData($user->id, $user->name))->values()->all()
                : [],
            'rejectionReasons' => RejectionReason::cases(),
            'interviewModes' => InterviewMode::cases(),
            'interviewOutcomes' => InterviewOutcome::cases(),
        ]);
    }

    public function move(MoveApplicantsRequest $request, MoveApplications $move): RedirectResponse
    {
        $organization = $this->organization($request);

        /** @var list<string> $ids */
        $ids = $request->array('application_ids');
        $applications = Application::query()
            ->whereIn('id', $ids)
            ->whereHas('job', fn ($query) => $query->where('organization_id', $organization->id))
            ->get();

        abort_if($applications->count() !== count(array_unique($ids)), 404);
        $applications->each(fn (Application $application) => Gate::authorize('manage', $application));

        $result = $move->handle($applications, $request->string('stage')->toString(), $request->user());
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['skipped'] > 0
                ? __('applications.stage.moved_some', ['moved' => $result['moved'], 'skipped' => $result['skipped']])
                : trans_choice('applications.stage.moved', $result['moved'], ['count' => $result['moved']]),
        ]);

        return back();
    }

    public function reject(RejectApplicantRequest $request, Application $application, RejectApplication $reject): RedirectResponse
    {
        $this->ownApplication($request, $application);
        Gate::authorize('manage', $application);

        $reject->handle(
            $application,
            RejectionReason::from($request->string('reason')->toString()),
            $request->filled('note') ? $request->string('note')->trim()->toString() : null,
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('applications.rejected')]);

        return to_route('org.applicants.show', $application);
    }

    /**
     * Reject many applicants at once, across one job or a recruitment project's jobs.
     */
    public function rejectMany(RejectApplicantsRequest $request, RejectApplications $reject): RedirectResponse
    {
        $organization = $this->organization($request);

        /** @var list<string> $ids */
        $ids = $request->array('application_ids');
        $applications = Application::query()
            ->whereIn('id', $ids)
            ->whereHas('job', fn ($query) => $query->where('organization_id', $organization->id))
            ->get();

        abort_if($applications->count() !== count(array_unique($ids)), 404);
        $applications->each(fn (Application $application) => Gate::authorize('manage', $application));

        $result = $reject->handle(
            $applications,
            RejectionReason::from($request->string('reason')->toString()),
            $request->filled('note') ? $request->string('note')->trim()->toString() : null,
            $request->user(),
        );
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['skipped'] > 0
                ? __('applications.stage.rejected_some', ['rejected' => $result['rejected'], 'skipped' => $result['skipped']])
                : trans_choice('applications.stage.rejected', $result['rejected'], ['count' => $result['rejected']]),
        ]);

        return back();
    }

    public function assign(AssignApplicantRequest $request, Application $application, AssignApplication $assign): RedirectResponse
    {
        $this->ownApplication($request, $application);
        Gate::authorize('manage', $application);

        $assignee = $request->filled('assigned_user_id') ? User::query()->find($request->string('assigned_user_id')->toString()) : null;
        $assign->handle($application, $assignee, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('applications.assign.saved')]);

        return to_route('org.applicants.show', $application);
    }

    public function scheduleInterview(ScheduleInterviewRequest $request, Application $application, ScheduleInterview $schedule): RedirectResponse
    {
        $this->ownApplication($request, $application);
        Gate::authorize('scheduleInterview', $application);

        $schedule->handle($application, $request->details(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('applications.interview.scheduled')]);

        return to_route('org.applicants.show', $application);
    }

    public function interviewOutcome(InterviewOutcomeRequest $request, Interview $interview, RecordInterviewOutcome $record): RedirectResponse
    {
        $this->ownApplication($request, $interview->application);
        Gate::authorize('scheduleInterview', $interview->application);

        $record->handle(
            $interview,
            InterviewOutcome::from($request->string('outcome')->toString()),
            $request->filled('note') ? $request->string('note')->trim()->toString() : null,
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('applications.interview.outcome_saved')]);

        return to_route('org.applicants.show', $interview->application);
    }

    public function sendOffer(SendOfferRequest $request, Application $application, SendOffer $send): RedirectResponse
    {
        $this->ownApplication($request, $application);
        Gate::authorize('manage', $application);

        $currency = $application->job->salary_currency ?? 'AED';
        $send->handle($application, [
            'salary_amount_minor' => Money::toMinor($request->integer('salary_amount'), $currency),
            'start_on' => $request->filled('start_on') ? $request->string('start_on')->toString() : null,
            'respond_by' => $request->filled('respond_by') ? $request->string('respond_by')->toString() : null,
            'note' => $request->filled('note') ? $request->string('note')->trim()->toString() : null,
        ], $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('applications.offer.sent')]);

        return to_route('org.applicants.show', $application);
    }

    private function ownJob(Request $request, JobPosting $job): Organization
    {
        $organization = $this->organization($request);
        abort_unless($job->organization_id === $organization->id, 404);

        return $organization;
    }

    private function ownApplication(Request $request, Application $application): Organization
    {
        return $this->ownJob($request, $application->job);
    }
}
