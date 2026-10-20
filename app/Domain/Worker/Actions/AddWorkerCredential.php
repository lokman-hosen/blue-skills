<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\UploadedUnreviewed;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * A certificate, training record or work reference the worker uploads (D-047). It stays
 * "uploaded · unreviewed" until staff verify it (Module 8); it is never shared without the
 * worker's consent per application (product rule 6).
 */
class AddWorkerCredential
{
    /** The credential types workers upload in Module 2. Passport and ID scans come in Module 4. */
    public const TYPES = [EvidenceType::Certificate, EvidenceType::Training, EvidenceType::EmployerReference];

    public function handle(WorkerProfile $profile, EvidenceType $type, string $title, ?string $expiresOn, UploadedFile $file): EvidenceItem
    {
        return DB::transaction(function () use ($profile, $type, $title, $expiresOn, $file): EvidenceItem {
            $document = Document::query()->create([
                'owner_user_id' => $profile->user_id,
                'type' => $type === EvidenceType::EmployerReference ? DocumentType::EmployerReference : DocumentType::Certificate,
                'title' => $title,
                'expires_at' => $expiresOn,
            ]);

            $document->addMedia($file)
                ->usingName($file->getClientOriginalName())
                ->usingFileName('credential.'.($file->guessExtension() ?? 'pdf'))
                ->toMediaCollection(Document::FILE_COLLECTION);

            /** @var EvidenceItem $evidence */
            $evidence = $profile->evidenceItems()->create([
                'type' => $type,
                'documentable_type' => $document->getMorphClass(),
                'documentable_id' => $document->id,
                'status' => UploadedUnreviewed::class,
            ]);

            activity('worker')->performedOn($evidence)->causedBy($profile->user)
                ->withProperties(['type' => $type->value, 'title' => $title])
                ->log('credential.uploaded');

            return $evidence;
        });
    }
}
