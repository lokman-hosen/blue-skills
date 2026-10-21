<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\Models\TrustCaseNote;
use Illuminate\Http\UploadedFile;

/**
 * Evidence on a case: a note with optional screenshots, call logs or payment records, kept on
 * the private disk (spec §10).
 */
class AddCaseNote
{
    /**
     * @param  list<UploadedFile>  $files
     */
    public function handle(TrustCase $case, string $body, array $files, User $actor): TrustCaseNote
    {
        $note = $case->notes()->create(['author_id' => $actor->id, 'body' => $body]);

        foreach ($files as $file) {
            $note->addMedia($file)->usingName($file->getClientOriginalName())->toMediaCollection(TrustCaseNote::FILES);
        }

        activity('trust')->performedOn($case)->causedBy($actor)
            ->withProperties(['note' => $note->id, 'files' => count($files)])
            ->log('trust_case.note_added');

        return $note;
    }
}
