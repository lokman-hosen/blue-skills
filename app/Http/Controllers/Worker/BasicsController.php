<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Taxonomy\Data\OccupationOptionData;
use App\Domain\Worker\Actions\SaveWorkerBasics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\SaveWorkerBasicsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Onboarding phase A (spec §3): trade and years of experience.
 */
class BasicsController extends Controller
{
    use ResolvesWorkerProfile;

    public function edit(Request $request): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        return Inertia::render('onboarding/worker/basics', [
            'occupations' => OccupationOptionData::catalog(),
            'occupationId' => $profile->occupation_id,
            'specializationId' => $profile->specialization_id,
            'yearsExperience' => $profile->years_experience,
        ]);
    }

    public function update(SaveWorkerBasicsRequest $request, SaveWorkerBasics $saveBasics): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $saveBasics->handle(
            $profile,
            $request->string('occupation_id')->toString(),
            $request->filled('specialization_id') ? $request->string('specialization_id')->toString() : null,
            $request->integer('years_experience'),
        );

        $intent = $request->string('intent')->toString();

        if ($intent === 'later') {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('worker.basics.saved_later')]);

            return to_route('dashboard');
        }

        return to_route('worker.voice.show', $intent === 'type' ? ['mode' => 'type'] : []);
    }
}
