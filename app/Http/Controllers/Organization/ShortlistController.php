<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Applications\Actions\AddToShortlist;
use App\Domain\Applications\Actions\CreateShortlist;
use App\Domain\Applications\Actions\DeleteShortlist;
use App\Domain\Applications\Actions\RemoveFromShortlist;
use App\Domain\Applications\Data\ShortlistData;
use App\Domain\Applications\Data\ShortlistEntryData;
use App\Domain\Applications\Models\Shortlist;
use App\Domain\Applications\Models\ShortlistEntry;
use App\Domain\Applications\Support\TalentAccess;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Worker\Models\WorkerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\AddShortlistEntryRequest;
use App\Http\Requests\Organization\StoreShortlistRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Named lists of workers the organization is considering (spec §14 "Shortlist").
 */
class ShortlistController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewAny', [Shortlist::class, $organization]);

        return Inertia::render('org/shortlists/index', [
            'shortlists' => $organization->shortlists()->with(['job', 'creator'])->withCount('entries')->latest()->get()
                ->map(fn (Shortlist $shortlist) => ShortlistData::fromShortlist($shortlist))->values()->all(),
            'jobs' => $organization->postedJobs()->latest('updated_at')->limit(50)->get(['public_code', 'title'])
                ->map(fn (JobPosting $job) => ['code' => $job->public_code, 'title' => $job->title])->values()->all(),
        ]);
    }

    public function store(StoreShortlistRequest $request, CreateShortlist $create): RedirectResponse
    {
        $organization = $this->organization($request);
        Gate::authorize('create', [Shortlist::class, $organization]);

        $job = $request->filled('job') ? JobPosting::query()->where('public_code', $request->string('job')->toString())->first() : null;
        $shortlist = $create->handle($organization, $request->string('name')->trim()->toString(), $job, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('shortlists.created')]);

        return $request->boolean('stay') ? back() : to_route('org.shortlists.show', $shortlist);
    }

    public function show(Request $request, Shortlist $shortlist): Response
    {
        $organization = $this->ownShortlist($request, $shortlist);
        Gate::authorize('manage', $shortlist);

        $entries = $shortlist->entries()
            ->with(['adder', 'workerProfile' => fn ($query) => $query->with(['user', 'occupation', 'currentCountry', 'experiences.country', 'workerSkills.skill', 'languages.language', 'skillShows', 'evidenceItems'])])
            ->get();

        return Inertia::render('org/shortlists/show', [
            'shortlist' => ShortlistData::fromShortlist($shortlist->loadMissing(['job', 'creator'])),
            'entries' => $entries->map(fn (ShortlistEntry $entry) => ShortlistEntryData::fromEntry(
                $entry,
                TalentAccess::canView($organization, $entry->workerProfile),
            ))->values()->all(),
        ]);
    }

    public function destroy(Request $request, Shortlist $shortlist, DeleteShortlist $delete): RedirectResponse
    {
        $this->ownShortlist($request, $shortlist);
        Gate::authorize('manage', $shortlist);

        $delete->handle($shortlist, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('shortlists.deleted')]);

        return to_route('org.shortlists.index');
    }

    public function addEntry(AddShortlistEntryRequest $request, Shortlist $shortlist, AddToShortlist $add): RedirectResponse
    {
        $this->ownShortlist($request, $shortlist);
        Gate::authorize('manage', $shortlist);

        $profile = WorkerProfile::query()->where('reference', $request->string('worker')->toString())->first();

        if ($profile === null) {
            return back()->withErrors(['worker' => __('shortlists.not_visible')]);
        }

        $add->handle($shortlist, $profile, $request->filled('note') ? $request->string('note')->trim()->toString() : null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('shortlists.added', ['list' => $shortlist->name])]);

        return back();
    }

    public function removeEntry(Request $request, Shortlist $shortlist, ShortlistEntry $entry, RemoveFromShortlist $remove): RedirectResponse
    {
        $this->ownShortlist($request, $shortlist);
        Gate::authorize('manage', $shortlist);
        abort_unless($entry->shortlist_id === $shortlist->id, 404);

        $remove->handle($entry, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('shortlists.removed')]);

        return back();
    }

    private function ownShortlist(Request $request, Shortlist $shortlist): Organization
    {
        $organization = $this->organization($request);
        abort_unless($shortlist->organization_id === $organization->id, 404);

        return $organization;
    }
}
