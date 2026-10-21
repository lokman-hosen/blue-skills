<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Mobility\Data\MobilityCaseData;
use App\Domain\Mobility\Models\MobilityCase;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The worker's journey after accepting an offer (prototype w-case, spec §6).
 */
class MobilityCaseController extends Controller
{
    public function show(MobilityCase $mobilityCase): Response
    {
        Gate::authorize('view', $mobilityCase);

        return Inertia::render('worker/cases/show', [
            'mobilityCase' => MobilityCaseData::fromCase($mobilityCase, forWorker: true),
        ]);
    }
}
