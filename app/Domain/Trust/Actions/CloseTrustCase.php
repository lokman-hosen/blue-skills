<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\ReportOutcome;
use App\Domain\Trust\Enums\TrustActionType;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\States\Report\Actioned;
use App\Domain\Trust\States\Report\Dismissed;
use App\Domain\Trust\States\Report\Investigating;
use App\Domain\Trust\States\TrustCase\Closed;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Closes a case with an outcome reason. Its reports close with it: "action taken" when the case
 * suspended anyone, otherwise "no action needed"; reporters are told only that (D-088).
 */
class CloseTrustCase
{
    public function handle(TrustCase $case, TrustReason $reason, ?string $note, User $actor): void
    {
        if ($case->isClosed()) {
            throw ValidationException::withMessages(['reason' => __('trust.errors.case_closed')]);
        }

        $actionTaken = $case->actions()->where('action', TrustActionType::Suspend)->exists();
        $reports = $case->reports()->with('reporter')->get();

        DB::transaction(function () use ($case, $reason, $note, $actor, $actionTaken, $reports): void {
            $case->status->transitionTo(Closed::class);
            $case->update(['outcome_code' => $reason->value, 'closed_by' => $actor->id, 'closed_at' => now()]);

            $reports->each(function (Report $report) use ($actionTaken, $reason, $actor): void {
                if ($report->status->equals(Investigating::class)) {
                    $report->status->transitionTo($actionTaken ? Actioned::class : Dismissed::class);
                }

                $report->update([
                    'outcome' => $actionTaken ? ReportOutcome::ActionTaken : ReportOutcome::NoAction,
                    'reason_code' => $reason,
                    'decided_by' => $actor->id,
                    'decided_at' => now(),
                ]);
            });

            if ($note !== null) {
                $case->notes()->create(['author_id' => $actor->id, 'body' => $note]);
            }

            activity('trust')->performedOn($case)->causedBy($actor)
                ->withProperties(['reason' => $reason->value, 'action_taken' => $actionTaken, 'reports' => $reports->count()])
                ->log('trust_case.closed');
        });

        $reports->each(fn (Report $report) => DismissReport::tellReporter($report->refresh()));
    }
}
