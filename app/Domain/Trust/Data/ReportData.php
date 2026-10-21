<?php

namespace App\Domain\Trust\Data;

use App\Domain\Messaging\Models\Message;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Support\TrustSubject;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A report in the trust queue (prototype x-trust).
 */
class ReportData extends Data
{
    public function __construct(
        public string $id,
        public ReportCategory $category,
        public string $description,
        /** Translated via `status.report_{value}`. */
        public StatusData $status,
        /** job | message */
        public string $about,
        public string $subject,
        /** The message text when a message was reported. */
        public ?string $excerpt,
        public ?string $reporter,
        public string $createdAt,
        public ?string $caseId,
        public ?string $caseReference,
        public ?string $outcome,
        public ?string $reasonCode,
    ) {}

    public static function fromReport(Report $report): self
    {
        $target = TrustSubject::fromReport($report);
        /** @var string $status */
        $status = $report->status->getValue();

        return new self(
            id: $report->id,
            category: $report->category,
            description: (string) $report->description,
            status: new StatusData('report_'.$status, $report->status->label(), $report->status->tone()),
            about: $report->subject_type,
            subject: TrustSubject::label($target),
            excerpt: $report->subject instanceof Message ? mb_strimwidth((string) $report->subject->body, 0, 200, '…') : null,
            reporter: $report->reporter?->name,
            createdAt: $report->created_at?->toIso8601String() ?? '',
            caseId: $report->trust_case_id,
            caseReference: $report->trustCase?->reference,
            outcome: $report->outcome?->value,
            reasonCode: $report->reason_code?->value,
        );
    }
}
