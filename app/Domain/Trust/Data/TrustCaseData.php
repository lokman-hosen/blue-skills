<?php

namespace App\Domain\Trust\Data;

use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\TrustAction;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\Models\TrustCaseNote;
use App\Domain\Worker\Support\MediaUrls;
use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A trust case page: reports, evidence notes with files, actions and appeals (spec §10).
 */
class TrustCaseData extends Data
{
    /**
     * @param  list<ReportData>  $reports
     * @param  list<array{id: string, body: string, author: string|null, at: string, files: list<array{name: string, url: string|null}>}>  $notes
     * @param  list<TrustActionData>  $actions
     */
    public function __construct(
        public TrustCaseListItemData $summary,
        public string $summaryText,
        public ?string $assigneeId,
        public ?string $outcomeCode,
        public ?string $closedAt,
        public array $reports,
        public array $notes,
        public array $actions,
    ) {}

    public static function fromCase(TrustCase $case): self
    {
        $case->loadMissing(['subject', 'assignee', 'reports.reporter', 'reports.subject', 'reports.trustCase', 'notes.author', 'notes.media', 'actions.actor', 'actions.subject', 'actions.appeals.appellant', 'actions.appeals.decider']);

        return new self(
            summary: TrustCaseListItemData::fromCase($case),
            summaryText: $case->summary,
            assigneeId: $case->assigned_to,
            outcomeCode: $case->outcome_code,
            closedAt: $case->closed_at?->toIso8601String(),
            reports: $case->reports->map(fn (Report $report) => ReportData::fromReport($report))->values()->all(),
            notes: $case->notes->map(fn (TrustCaseNote $note) => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->author?->name,
                'at' => $note->created_at?->toIso8601String() ?? '',
                'files' => $note->getMedia(TrustCaseNote::FILES)->map(fn (Media $media) => ['name' => $media->name, 'url' => MediaUrls::signed($media)])->values()->all(),
            ])->values()->all(),
            actions: $case->actions->map(fn (TrustAction $action) => TrustActionData::fromAction($action))->values()->all(),
        );
    }
}
