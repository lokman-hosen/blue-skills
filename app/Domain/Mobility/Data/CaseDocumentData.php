<?php

namespace App\Domain\Mobility\Data;

use App\Domain\Mobility\Enums\CaseDocumentReturnReason;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Support\CaseChecklist;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Support\MediaUrls;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * One checklist item as a viewer of the case sees it (prototype w-case "Documents & who can see
 * them"). The file link is there only for people the item is shared with (product rule 6).
 */
class CaseDocumentData extends Data
{
    public const VIEWER_WORKER = 'worker';

    public const VIEWER_MANAGER = 'manager';

    public const VIEWER_EMPLOYER = 'employer';

    /**
     * @param  list<string>  $visibleTo  Organization names besides the worker; empty while only the worker has it.
     */
    public function __construct(
        public string $id,
        public DocumentType $type,
        public string $title,
        /** Translated via `status.{value}`. */
        public StatusData $status,
        public DocumentProvider $providedBy,
        public bool $employerCanView,
        public array $visibleTo,
        public bool $revoked,
        public ?string $fileName,
        public ?string $fileUrl,
        public ?string $sharedAt,
        public ?string $decidedAt,
        public ?string $dueOn,
        public ?CaseDocumentReturnReason $returnReason,
        public ?string $note,
    ) {}

    /**
     * Items an employer following an agency case may see; everyone else sees the whole list.
     *
     * @return list<self>
     */
    public static function forViewer(MobilityCase $case, string $viewer): array
    {
        $case->loadMissing(['documents.document.media', 'managingOrganization', 'employer']);

        return $case->documents
            ->filter(fn (CaseDocument $item) => $viewer !== self::VIEWER_EMPLOYER || $item->employer_can_view)
            ->map(fn (CaseDocument $item) => self::fromItem($item, $case, $viewer))
            ->values()
            ->all();
    }

    public static function fromItem(CaseDocument $item, MobilityCase $case, string $viewer): self
    {
        $document = $item->document;
        $hasFile = $document instanceof Document;
        $visible = $hasFile && ($viewer === self::VIEWER_WORKER || ! $item->fromWorker() || $item->isShared());
        $media = $visible ? $document->getFirstMedia(Document::FILE_COLLECTION) : null;
        $revoked = $item->fromWorker() && $item->revoked_at !== null && $item->status === CaseDocumentStatus::Requested;

        return new self(
            id: $item->id,
            type: $item->type,
            title: self::title($item),
            status: self::status($item, $revoked),
            providedBy: $item->provided_by,
            employerCanView: $item->employer_can_view,
            visibleTo: self::visibleTo($item, $case),
            revoked: $revoked,
            fileName: $media?->name,
            fileUrl: MediaUrls::signed($media),
            sharedAt: $item->shared_at?->toIso8601String(),
            decidedAt: $item->decided_at?->toIso8601String(),
            dueOn: $item->due_on?->toDateString(),
            returnReason: $item->reason_code !== null ? CaseDocumentReturnReason::tryFrom($item->reason_code) : null,
            note: $item->note,
        );
    }

    /**
     * Checklist titles are translated; titles an organization typed stay as written.
     */
    public static function title(CaseDocument $item): string
    {
        return $item->title === CaseChecklist::title($item->type) ? __("mobility.document.type.{$item->type->value}") : $item->title;
    }

    /**
     * `doc_contract_sent` while a contract waits for the worker; `doc_revoked` once the worker
     * stopped sharing.
     */
    private static function status(CaseDocument $item, bool $revoked): StatusData
    {
        return match (true) {
            $revoked => new StatusData('doc_revoked', 'Sharing stopped', 'bad'),
            ! $item->fromWorker() && $item->status === CaseDocumentStatus::Uploaded => new StatusData('doc_contract_sent', 'Waiting for the worker', 'stamp'),
            default => new StatusData('doc_'.$item->status->value, $item->status->label(), $item->status->tone()),
        };
    }

    /**
     * @return list<string>
     */
    private static function visibleTo(CaseDocument $item, MobilityCase $case): array
    {
        if ($item->fromWorker() && ! $item->isShared()) {
            return [];
        }

        $names = [$case->managingOrganization?->display_name];

        if ($item->employer_can_view && $case->employer !== null && ! $case->employer->is($case->managingOrganization)) {
            $names[] = $case->employer->display_name;
        }

        return array_values(array_filter($names));
    }
}
