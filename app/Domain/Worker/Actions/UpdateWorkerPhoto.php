<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Http\UploadedFile;

/**
 * The optional profile photo (spec §3), kept on the private disk.
 */
class UpdateWorkerPhoto
{
    public function handle(WorkerProfile $profile, ?UploadedFile $photo): void
    {
        if ($photo === null) {
            $profile->clearMediaCollection(WorkerProfile::PHOTO_COLLECTION);
        } else {
            $profile->addMedia($photo)
                ->usingFileName('photo.'.($photo->guessExtension() ?? 'jpg'))
                ->toMediaCollection(WorkerProfile::PHOTO_COLLECTION);
        }

        activity('worker')->performedOn($profile)->causedBy($profile->user)
            ->log($photo === null ? 'profile.photo_removed' : 'profile.photo_updated');
    }
}
