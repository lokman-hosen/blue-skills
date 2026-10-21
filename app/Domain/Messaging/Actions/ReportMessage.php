<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Models\Message;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\States\Report\Open;

/**
 * Either side reports a message (payment requests, harassment, impersonation…). Trust staff
 * review it in Module 8; nothing is blocked automatically (product rule 14).
 */
class ReportMessage
{
    /** Categories that make sense for a message. */
    public const CATEGORIES = [ReportCategory::UnauthorizedPayment, ReportCategory::Harassment, ReportCategory::Impersonation, ReportCategory::Other];

    public function handle(Message $message, User $reporter, ReportCategory $category, ?string $description): Report
    {
        $report = Report::query()->create([
            'reporter_user_id' => $reporter->id,
            'subject_type' => $message->getMorphClass(),
            'subject_id' => $message->id,
            'category' => $category,
            'description' => $description ?? '',
            'status' => Open::class,
        ]);

        activity('trust')->performedOn($message->conversation)->causedBy($reporter)
            ->withProperties(['report' => $report->id, 'message' => $message->id, 'category' => $category->value])
            ->log('message.reported');

        return $report;
    }
}
