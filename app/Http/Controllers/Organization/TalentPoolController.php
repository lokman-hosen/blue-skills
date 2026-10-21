<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Institute\Actions\AddPoolMembers;
use App\Domain\Institute\Actions\PublishTalentPool;
use App\Domain\Institute\Actions\RemovePoolMember;
use App\Domain\Institute\Actions\SaveTalentPool;
use App\Domain\Institute\Data\PoolMemberData;
use App\Domain\Institute\Data\TalentPoolData;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Models\WorkerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\AddPoolMembersRequest;
use App\Http\Requests\Organization\SaveTalentPoolRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The institute's talent pools of graduates who accepted (spec §8, D-082).
 */
class TalentPoolController extends Controller
{
    use ResolvesCurrentOrganization;

    private const PROFILE_RELATIONS = ['user', 'occupation', 'skillShows', 'evidenceItems'];

    public function index(Request $request): Response
    {
        $institute = $this->organization($request);
        Gate::authorize('viewAny', [TalentPool::class, $institute]);

        return Inertia::render('org/pools/index', [
            'pools' => $institute->talentPools()->latest()->get()->map(fn (TalentPool $pool) => TalentPoolData::fromPool($pool))->values()->all(),
            'occupations' => Occupation::query()->orderBy('slug')->get()->map(fn (Occupation $occupation) => TaxonomyOptionData::fromModel($occupation))->values()->all(),
            'instituteVerified' => $institute->isVerified(),
        ]);
    }

    public function store(SaveTalentPoolRequest $request, SaveTalentPool $save): RedirectResponse
    {
        $institute = $this->organization($request);
        Gate::authorize('viewAny', [TalentPool::class, $institute]);

        $pool = $save->handle($institute, null, $this->fields($request), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('pools.created')]);

        return to_route('org.pools.show', $pool);
    }

    public function show(Request $request, TalentPool $pool): Response
    {
        $this->ownPool($request, $pool);
        Gate::authorize('update', $pool);

        $members = $pool->workers()->with(self::PROFILE_RELATIONS)->get();
        $eligible = WorkerProfile::query()
            ->whereKey(AddPoolMembers::eligibleProfileIds($pool))
            ->whereKeyNot($members->modelKeys())
            ->with(self::PROFILE_RELATIONS)
            ->get();
        $visible = WorkerProfile::query()->whereKey([...$members->modelKeys(), ...$eligible->modelKeys()])->discoverable()->pluck('id')->all();

        return Inertia::render('org/pools/show', [
            'pool' => TalentPoolData::fromPool($pool),
            'members' => $members->map(fn (WorkerProfile $profile) => PoolMemberData::fromProfile($profile, in_array($profile->id, $visible, true)))->values()->all(),
            'available' => $eligible->map(fn (WorkerProfile $profile) => PoolMemberData::fromProfile($profile, in_array($profile->id, $visible, true)))->values()->all(),
            'occupations' => Occupation::query()->orderBy('slug')->get()->map(fn (Occupation $occupation) => TaxonomyOptionData::fromModel($occupation))->values()->all(),
        ]);
    }

    public function update(SaveTalentPoolRequest $request, TalentPool $pool, SaveTalentPool $save): RedirectResponse
    {
        $this->ownPool($request, $pool);
        Gate::authorize('update', $pool);

        $save->handle($pool->organization, $pool, $this->fields($request), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('pools.saved')]);

        return to_route('org.pools.show', $pool);
    }

    public function publish(Request $request, TalentPool $pool, string $state, PublishTalentPool $publish): RedirectResponse
    {
        $this->ownPool($request, $pool);
        Gate::authorize('update', $pool);

        $publish->handle($pool, $state === 'publish', $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("pools.done.{$state}")]);

        return to_route('org.pools.show', $pool);
    }

    public function addMembers(AddPoolMembersRequest $request, TalentPool $pool, AddPoolMembers $add): RedirectResponse
    {
        $this->ownPool($request, $pool);
        Gate::authorize('update', $pool);

        /** @var list<string> $profiles */
        $profiles = $request->array('profiles');
        $added = $add->handle($pool, $profiles, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice('pools.added', $added, ['count' => $added])]);

        return to_route('org.pools.show', $pool);
    }

    public function removeMember(Request $request, TalentPool $pool, WorkerProfile $profile, RemovePoolMember $remove): RedirectResponse
    {
        $this->ownPool($request, $pool);
        Gate::authorize('update', $pool);

        $remove->handle($pool, $profile, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('pools.removed')]);

        return to_route('org.pools.show', $pool);
    }

    private function ownPool(Request $request, TalentPool $pool): void
    {
        abort_unless($pool->organization_id === $this->organization($request)->id, 404);
    }

    /**
     * @return array{name: string, description: string|null, occupation_id: string|null}
     */
    private function fields(SaveTalentPoolRequest $request): array
    {
        return [
            'name' => $request->string('name')->trim()->toString(),
            'description' => $request->string('description')->trim()->toString() ?: null,
            'occupation_id' => $request->string('occupation_id')->toString() ?: null,
        ];
    }
}
