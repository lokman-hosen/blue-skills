<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Trust\Enums\ReportOutcome;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\States\Report\Dismissed;
use App\Domain\Trust\States\Report\Open;
use Illuminate\Validation\ValidationException;

/**
 * Closes a report that needs no case, with a reason code. The reporter is told only that it was
 * reviewed and no action was needed (D-088).
 */
class DismissReport
{
    public function handle(Report $report, TrustReason $reason, ?string $note, User $actor): void
    {
        if (! $report->status->equals(Open::class) || $report->trust_case_id !== null) {
            throw ValidationException::withMessages(['reason' => __('trust.errors.report_not_open')]);
        }

        if (! in_array($reason, TrustReason::forDismissal(), true)) {
            throw ValidationException::withMessages(['reason' => __('trust.errors.reason')]);
        }

        $report->status->transitionTo(Dismissed::class);
        $report->update(['outcome' => ReportOutcome::NoAction, 'reason_code' => $reason, 'decided_by' => $actor->id, 'decided_at' => now()]);

        activity('trust')->performedOn($report)->causedBy($actor)
            ->withProperties(['reason' => $reason->value, 'note' => $note])
            ->log('report.dismissed');

        self::tellReporter($report);
    }

    public static function tellReporter(Report $report): void
    {
        if ($report->reporter !== null && $report->outcome !== null) {
            Notify::user($report->reporter, NotificationEvent::ReportOutcome, [
                'report_outcome' => $report->outcome->value,
            ], route('notifications.index', absolute: false));
        }
    }
}
