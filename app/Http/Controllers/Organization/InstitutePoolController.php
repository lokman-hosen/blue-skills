<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Applications\Data\TalentCardData;
use App\Domain\Institute\Data\TalentPoolData;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Worker\Models\WorkerProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Published institute pools for verified employers and agencies (spec §8, D-082). They see only
 * graduates who chose to be found and reach them through the talent profile's "invite to apply".
 */
class InstitutePoolController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('browse', [TalentPool::class, $organization]);

        $pools = TalentPool::query()
            ->whereNotNull('published_at')
            ->whereHas('organization', fn ($institutes) => $institutes->whereState('verification_status', Verified::class))
            ->latest('published_at')
            ->get();

        return Inertia::render('org/institute-pools/index', [
            'pools' => $pools->map(fn (TalentPool $pool) => TalentPoolData::fromPool($pool))->values()->all(),
        ]);
    }

    public function show(Request $request, TalentPool $pool): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('view', [$pool, $organization]);
        abort_unless($pool->organization->isVerified(), 404);

        $workers = $pool->workers()->discoverable()
            ->with(['user', 'occupation', 'currentCountry', 'experiences.country', 'workerSkills.skill', 'languages.language', 'skillShows', 'evidenceItems'])
            ->get();

        return Inertia::render('org/institute-pools/show', [
            'pool' => TalentPoolData::fromPool($pool),
            'workers' => $workers->map(fn (WorkerProfile $profile) => TalentCardData::fromProfile($profile, null))->values()->all(),
        ]);
    }
}
