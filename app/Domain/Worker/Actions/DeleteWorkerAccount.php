<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Applications\Models\Consent;
use App\Domain\Identity\Models\User;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\VoiceAnswer;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a worker account after a fresh phone code (D-044, D-049). The account is soft
 * deleted so the number stays blocked (D-037); the Passport is hidden, every consent revoked,
 * and recordings, SkillShows, photo and uploaded files removed. Application snapshots already
 * shared stay with those applications (product rule 7).
 */
class DeleteWorkerAccount
{
    public function handle(User $worker): void
    {
        DB::transaction(function () use ($worker): void {
            $profile = $worker->workerProfile;

            if ($profile instanceof WorkerProfile) {
                $profile->consents()->active()->each(fn (Consent $consent) => $consent->update(['revoked_at' => now()]));
                $profile->voiceAnswers()->each(fn (VoiceAnswer $answer) => $answer->clearMediaCollection(VoiceAnswer::AUDIO_COLLECTION));
                $profile->skillShows()->each(fn (SkillShow $skillShow) => $skillShow->delete());
                $profile->clearMediaCollection(WorkerProfile::PHOTO_COLLECTION);
                $profile->update(['visibility' => ProfileVisibility::Hidden]);
            }

            $worker->documents()->each(function (Document $document): void {
                $document->clearMediaCollection(Document::FILE_COLLECTION);
                $document->delete();
            });

            activity('identity')->performedOn($worker)->causedBy($worker)->log('worker.deleted');

            $worker->delete();
        });
    }
}
