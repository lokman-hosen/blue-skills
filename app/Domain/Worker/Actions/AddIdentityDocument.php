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
 * A passport or national ID scan on the Documents page (D-047). One of each type: a new upload
 * replaces the old one, and any application that had the old scan stops seeing it. Scans are
 * never in search results and are shared only per application with consent (product rule 6).
 */
class AddIdentityDocument
{
    public const TYPES = [DocumentType::Passport, DocumentType::NationalId];

    public function __construct(private readonly RemoveIdentityDocument $remove) {}

    public function handle(WorkerProfile $profile, DocumentType $type, ?string $expiresOn, UploadedFile $file): Document
    {
        return DB::transaction(function () use ($profile, $type, $expiresOn, $file): Document {
            Document::query()
                ->where('owner_user_id', $profile->user_id)
                ->where('type', $type)
                ->get()
                ->each(fn (Document $previous) => $this->remove->handle($profile, $previous));

            $document = Document::query()->create([
                'owner_user_id' => $profile->user_id,
                'type' => $type,
                'title' => $type->label(),
                'expires_at' => $expiresOn,
            ]);

            $document->addMedia($file)
                ->usingName($file->getClientOriginalName())
                ->usingFileName($type->value.'.'.($file->guessExtension() ?? 'pdf'))
                ->toMediaCollection(Document::FILE_COLLECTION);

            /** @var EvidenceItem $evidence */
            $evidence = $profile->evidenceItems()->create([
                'type' => $type === DocumentType::Passport ? EvidenceType::Passport : EvidenceType::Identity,
                'documentable_type' => $document->getMorphClass(),
                'documentable_id' => $document->id,
                'status' => UploadedUnreviewed::class,
            ]);

            activity('worker')->performedOn($evidence)->causedBy($profile->user)
                ->withProperties(['type' => $type->value])
                ->log('identity_document.uploaded');

            return $document;
        });
    }
}
