<?php

namespace App\Domain\Verification\Data;

use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Worker\Support\MediaUrls;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A worker's uploaded evidence in the staff review queue (D-090). The file link is only there
 * for a reviewer who started the review.
 */
class EvidenceReviewData extends Data
{
    /**
     * @param  list<ReviewDecisionData>  $history
     */
    public function __construct(
        public string $id,
        public EvidenceType $type,
        public ?string $title,
        /** Translated via `evidence.state.{value}`. */
        public StatusData $state,
        public string $workerName,
        public string $passportReference,
        public string $uploadedAt,
        public ?string $expiresOn,
        public ?string $reviewer,
        public ?string $fileUrl,
        public ?string $fileName,
        public array $history,
    ) {}

    public static function fromItem(EvidenceItem $item, bool $withFile = false): self
    {
        $document = $item->documentable instanceof Document ? $item->documentable : null;
        $media = $withFile ? $document?->getFirstMedia(Document::FILE_COLLECTION) : null;
        /** @var VerificationRequest|null $open */
        $open = $item->verificationRequests->first(fn (VerificationRequest $request) => in_array($request->status->getValue(), ['open', 'in_review'], true));

        return new self(
            id: $item->id,
            type: $item->type,
            title: $document?->title,
            state: StatusData::fromState($item->status),
            workerName: (string) $item->workerProfile->user?->name,
            passportReference: (string) $item->workerProfile->reference,
            uploadedAt: $item->created_at?->toIso8601String() ?? '',
            expiresOn: $document?->expires_at?->toDateString(),
            reviewer: $open?->assignee?->name,
            fileUrl: MediaUrls::signed($media),
            fileName: $media?->name,
            history: $item->verificationRequests->sortByDesc('created_at')
                ->map(fn (VerificationRequest $request) => ReviewDecisionData::fromRequest($request, withReviewer: true))
                ->filter()->values()->all(),
        );
    }
}
