<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Applications\Actions\InviteToApply;
use App\Domain\Applications\Data\JobInvitationData;
use App\Domain\Applications\Data\ShortlistData;
use App\Domain\Applications\Data\TalentCardData;
use App\Domain\Applications\Data\TalentProfileData;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Applications\Models\Shortlist;
use App\Domain\Applications\Support\TalentAccess;
use App\Domain\Applications\Support\TalentSearch;
use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\Language;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\Support\JobMatch;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Models\WorkerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\InviteToApplyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Talent search (spec §8, deck slide 17): structured filters over workers who chose to be
 * found, practical skills and SkillShow first, contact details never.
 */
class TalentController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('search', [WorkerProfile::class, $organization]);

        $filters = [
            'q' => mb_substr($request->string('q')->trim()->toString(), 0, 100) ?: null,
            'occupation' => $request->string('occupation')->toString() ?: null,
            'min_years' => $request->integer('min_years') > 0 ? min($request->integer('min_years'), 40) : null,
            'country' => $request->string('country')->toString() ?: null,
            'worked_in' => $request->string('worked_in')->toString() ?: null,
            'passport_ready' => $request->boolean('passport_ready'),
            'available_soon' => $request->boolean('available_soon'),
            'language' => $request->string('language')->toString() ?: null,
            'verified_evidence' => $request->boolean('verified_evidence'),
            'skillshow' => $request->boolean('skillshow'),
        ];
        $job = $request->filled('job')
            ? $organization->postedJobs()->where('public_code', $request->string('job')->toString())->with(['skills', 'occupation'])->first()
            : null;
        $verified = $organization->isVerified();

        return Inertia::render('org/talent/index', [
            'verified' => $verified,
            'results' => $verified
                ? TalentSearch::run($filters)->through(fn (WorkerProfile $profile) => TalentCardData::fromProfile($profile, $job !== null ? JobMatch::explain($profile, $job) : null))
                : null,
            'filters' => [...$filters, 'job' => $job?->public_code],
            'options' => [
                'occupations' => Occupation::query()->orderBy('slug')->get()->map(fn (Occupation $occupation) => TaxonomyOptionData::fromModel($occupation))->values()->all(),
                'countries' => Country::query()->orderBy('name')->get()->map(fn (Country $country) => CountryOptionData::fromCountry($country))->values()->all(),
                'languages' => Language::query()->orderBy('code')->get(['code', 'native_name'])->map(fn (Language $language) => ['code' => $language->code, 'name' => $language->native_name])->values()->all(),
                'jobs' => $organization->postedJobs()->latest('updated_at')->limit(50)->get(['public_code', 'title'])
                    ->map(fn (JobPosting $posting) => ['code' => $posting->public_code, 'title' => $posting->title])->values()->all(),
            ],
        ]);
    }

    public function invite(InviteToApplyRequest $request, WorkerProfile $profile, InviteToApply $invite): RedirectResponse
    {
        $organization = $this->organization($request);
        Gate::authorize('create', [JobInvitation::class, $organization]);

        $job = $organization->postedJobs()->where('public_code', $request->string('job')->toString())->first();
        abort_if($job === null, 422);

        $invite->handle(
            $organization,
            $job,
            $profile,
            $request->filled('note') ? $request->string('note')->trim()->toString() : null,
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('invitations.sent')]);

        return back();
    }

    public function show(Request $request, WorkerProfile $profile): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('search', [WorkerProfile::class, $organization]);
        abort_unless(TalentAccess::canView($organization, $profile), 404);

        $applications = $profile->applications()
            ->whereHas('job', fn ($query) => $query->where('organization_id', $organization->id))
            ->with('job')
            ->get();

        return Inertia::render('org/talent/show', [
            'profile' => TalentProfileData::fromProfile($profile),
            'applications' => $applications->map(fn (Application $application) => [
                'id' => $application->id,
                'jobCode' => $application->job->public_code,
                'jobTitle' => $application->job->title,
            ])->values()->all(),
            'shortlists' => Gate::allows('viewAny', [Shortlist::class, $organization])
                ? $organization->shortlists()->with(['job', 'creator'])->withCount('entries')->latest()->get()
                    ->map(fn (Shortlist $shortlist) => ShortlistData::fromShortlist($shortlist))->values()->all()
                : null,
            'canInvite' => Gate::allows('create', [JobInvitation::class, $organization]),
            'openJobs' => $organization->postedJobs()->whereState('status', Published::class)->latest('published_at')->get()
                ->filter(fn (JobPosting $posting) => $posting->acceptsApplications())
                ->map(fn (JobPosting $posting) => ['code' => $posting->public_code, 'title' => $posting->title])->values()->all(),
            'invitations' => JobInvitation::query()->where('organization_id', $organization->id)->where('worker_profile_id', $profile->id)
                ->with(['job.employer', 'job.destinationCountry', 'job.costs', 'job.occupation', 'organization'])->latest()->get()
                ->map(fn (JobInvitation $invitation) => JobInvitationData::fromInvitation($invitation))->values()->all(),
            'onShortlists' => $organization->shortlists()->whereHas('entries', fn ($query) => $query->where('worker_profile_id', $profile->id))->pluck('id')->all(),
        ]);
    }
}
