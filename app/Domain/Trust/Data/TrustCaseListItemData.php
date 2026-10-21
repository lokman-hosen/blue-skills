<?php

namespace App\Domain\Trust\Data;

use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\Support\TrustSubject;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

class TrustCaseListItemData extends Data
{
    public function __construct(
        public string $id,
        public string $reference,
        public ReportCategory $category,
        /** Translated via `status.trust_{value}`. */
        public StatusData $status,
        /** Translated via `status.priority_{value}`. */
        public StatusData $priority,
        public string $subjectType,
        public string $subject,
        public bool $subjectSuspended,
        public ?string $assignee,
        public int $reports,
        public string $openedAt,
    ) {}

    public static function fromCase(TrustCase $case): self
    {
        /** @var string $status */
        $status = $case->status->getValue();

        return new self(
            id: $case->id,
            reference: $case->reference,
            category: $case->category,
            status: new StatusData('trust_'.$status, $case->status->label(), $case->status->tone()),
            priority: new StatusData('priority_'.$case->priority->value, $case->priority->label(), $case->priority->tone()),
            subjectType: $case->subject_type,
            subject: TrustSubject::label($case->subject),
            subjectSuspended: $case->subject !== null && TrustSubject::isSuspended($case->subject),
            assignee: $case->assignee?->name,
            reports: $case->reports_count ?? $case->reports->count(),
            openedAt: $case->created_at?->toIso8601String() ?? '',
        );
    }
}
