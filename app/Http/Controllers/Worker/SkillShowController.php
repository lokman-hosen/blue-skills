<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Worker\Actions\DeleteSkillShow;
use App\Domain\Worker\Actions\SubmitSkillShow;
use App\Domain\Worker\Actions\UpdateSkillShowDetails;
use App\Domain\Worker\Data\ProfileOptionsData;
use App\Domain\Worker\Data\SkillShowData;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Models\SkillShow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\SkillShowRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The worker's 30-second SkillShow (spec §3 SkillShow workflow).
 */
class SkillShowController extends Controller
{
    use ResolvesWorkerProfile;

    public function edit(Request $request): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $skillShow = $profile->skillShows()->latest()->first();

        return Inertia::render('worker/skillshow', [
            'skillShow' => $skillShow !== null ? SkillShowData::fromSkillShow($skillShow) : null,
            'occupationSlug' => $profile->occupation?->slug,
            'options' => fn () => ProfileOptionsData::load(),
            'maxSeconds' => SkillShow::MAX_DURATION_MS / 1000,
            'maxSizeMb' => SkillShow::MAX_UPLOAD_KB / 1024,
            'acceptedTypes' => SkillShow::ACCEPTED_MIME_TYPES,
            'maxSkills' => SkillShowRequest::MAX_SKILLS,
        ]);
    }

    public function store(SkillShowRequest $request, SubmitSkillShow $submit): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $video = $request->file('video');
        abort_unless($video instanceof UploadedFile, 422);

        $submit->handle($profile, $video, $request->skillIds(), $request->visibility());

        return to_route('worker.skillshow.edit');
    }

    public function update(SkillShowRequest $request, SkillShow $skillShow, UpdateSkillShowDetails $update): RedirectResponse
    {
        Gate::authorize('update', $skillShow);
        abort_if($skillShow->moderation_status === ModerationStatus::Failed, 409);

        $update->handle($skillShow, $request->skillIds(), $request->visibility());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('skillshow.saved')]);

        return to_route('worker.skillshow.edit');
    }

    public function destroy(SkillShow $skillShow, DeleteSkillShow $delete): RedirectResponse
    {
        Gate::authorize('delete', $skillShow);

        $delete->handle($skillShow);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('skillshow.deleted')]);

        return to_route('worker.skillshow.edit');
    }
}
