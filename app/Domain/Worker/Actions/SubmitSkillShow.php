<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Jobs\ProcessSkillShowVideo;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Record or upload → server check → moderation → publish (spec §3). A worker keeps one
 * SkillShow; a new one replaces the old.
 */
class SubmitSkillShow
{
    public function __construct(private readonly DeleteSkillShow $delete) {}

    /**
     * @param  list<string>  $skillIds
     */
    public function handle(WorkerProfile $profile, UploadedFile $video, array $skillIds, ProfileVisibility $visibility): SkillShow
    {
        $skillShow = DB::transaction(function () use ($profile, $video, $skillIds, $visibility): SkillShow {
            $profile->skillShows()->get()->each(fn (SkillShow $previous) => $this->delete->handle($previous));

            /** @var SkillShow $skillShow */
            $skillShow = $profile->skillShows()->create([
                'visibility' => $visibility,
                'moderation_status' => ModerationStatus::Processing,
            ]);
            $skillShow->skills()->sync($skillIds);

            $skillShow->addMedia($video)
                ->usingFileName('upload.'.($video->guessExtension() ?? 'mp4'))
                ->toMediaCollection(SkillShow::UPLOAD_COLLECTION);

            activity('worker')->performedOn($skillShow)->causedBy($profile->user)
                ->withProperties(['skills' => $skillIds, 'visibility' => $visibility->value])
                ->log('skill_show.submitted');

            return $skillShow;
        });

        ProcessSkillShowVideo::dispatch($skillShow)->afterCommit();

        return $skillShow;
    }
}
