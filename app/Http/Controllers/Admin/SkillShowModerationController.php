<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Worker\Actions\ModerateSkillShow;
use App\Domain\Worker\Data\SkillShowReviewData;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\SkillShowRejectionReason;
use App\Domain\Worker\Models\SkillShow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateSkillShowRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Minimal SkillShow moderation queue (product rule 3). The full verification queues come in
 * Module 8.
 */
class SkillShowModerationController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('moderate', SkillShow::class);

        return Inertia::render('admin/skillshows', [
            'queue' => SkillShow::query()
                ->with(['workerProfile.user', 'workerProfile.occupation', 'skills', 'media'])
                ->where('moderation_status', ModerationStatus::Pending)
                ->oldest()
                ->limit(50)
                ->get()
                ->map(fn (SkillShow $skillShow) => SkillShowReviewData::fromSkillShow($skillShow))
                ->values()
                ->all(),
            'reasons' => SkillShowRejectionReason::cases(),
        ]);
    }

    public function update(ModerateSkillShowRequest $request, SkillShow $skillShow, ModerateSkillShow $moderate): RedirectResponse
    {
        Gate::authorize('moderate', $skillShow);

        $moderate->handle($skillShow, $request->user(), $request->approves(), $request->reason());
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->approves() ? __('admin.skillshows.approved') : __('admin.skillshows.rejected'),
        ]);

        return to_route('admin.skillshows.index');
    }
}
