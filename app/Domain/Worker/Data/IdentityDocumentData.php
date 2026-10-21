<?php

namespace App\Domain\Worker\Data;

use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Support\MediaUrls;
use Spatie\LaravelData\Data;

/**
 * A passport or ID scan as its owner sees it, with every application it is shared with.
 */
class IdentityDocumentData extends Data
{
    /**
     * @param  list<string>  $sharedWith  Public job codes.
     */
    public function __construct(
        public string $id,
        public DocumentType $type,
        public ?string $expiresOn,
        public string $uploadedAt,
        /** Evidence state (product rule 9). */
        public ?string $state,
        public ?string $fileUrl,
        public array $sharedWith,
    ) {}

    public static function fromDocument(Document $document): self
    {
        $evidence = $document->evidenceItems->first();
        $sharedWith = ApplicationConsent::query()->active()
            ->where('document_id', $document->id)
            ->with('application.job')
            ->get()
            ->map(fn (ApplicationConsent $consent) => $consent->application->job->public_code)
            ->values()
            ->all();

        return new self(
            id: $document->id,
            type: $document->type,
            expiresOn: $document->expires_at?->toDateString(),
            uploadedAt: $document->created_at?->toIso8601String() ?? '',
            state: $evidence instanceof EvidenceItem ? (string) $evidence->status->getValue() : null,
            fileUrl: MediaUrls::signed($document->getFirstMedia(Document::FILE_COLLECTION)),
            sharedWith: $sharedWith,
        );
    }
}
