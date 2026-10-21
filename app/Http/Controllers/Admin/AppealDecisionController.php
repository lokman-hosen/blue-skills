<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Trust\Actions\DecideAppeal;
use App\Domain\Trust\Models\Appeal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideAppealRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Another trust officer upholds or overturns an appeal (D-088).
 */
class AppealDecisionController extends Controller
{
    public function __invoke(DecideAppealRequest $request, Appeal $appeal, string $decision, DecideAppeal $decide): RedirectResponse
    {
        Gate::authorize('decide', $appeal);

        $decide->handle($appeal, $decision === 'overturn', $request->string('note')->trim()->toString(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("trust.done.appeal_{$decision}")]);

        return back();
    }
}
