<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ContactChannel;
use App\Domain\Crm\Enums\ContactOutcome;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadContactAttempt;
use App\Domain\Identity\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records a call, message or visit and moves the follow-up status to match the outcome (spec §9).
 * Nobody who asked not to be contacted can be logged as contacted.
 */
class LogContactAttempt
{
    public function handle(Lead $lead, ContactChannel $channel, ContactOutcome $outcome, ?string $note, ?CarbonInterface $nextFollowUpAt, User $agent): LeadContactAttempt
    {
        if (! $lead->mayBeContacted()) {
            throw ValidationException::withMessages(['outcome' => __('crm.errors.do_not_contact')]);
        }

        return DB::transaction(function () use ($lead, $channel, $outcome, $note, $nextFollowUpAt, $agent): LeadContactAttempt {
            $attempt = $lead->contactAttempts()->create([
                'staff_id' => $agent->id,
                'channel' => $channel,
                'outcome' => $outcome,
                'note' => $note,
                'attempted_at' => now(),
            ]);

            $status = match ($outcome) {
                ContactOutcome::Reached => FollowUpStatus::Contacted,
                ContactOutcome::NoAnswer, ContactOutcome::WrongNumber => FollowUpStatus::NoAnswer,
                ContactOutcome::CallBack => FollowUpStatus::CallBack,
                ContactOutcome::NotInterested => FollowUpStatus::NotInterested,
            };

            $lead->update([
                'follow_up_status' => $status,
                'last_contacted_at' => now(),
                'next_follow_up_at' => $status === FollowUpStatus::NotInterested ? null : $nextFollowUpAt,
                // Whoever makes contact first takes the lead.
                'assigned_to' => $lead->assigned_to ?? $agent->id,
            ]);

            return $attempt;
        });
    }
}
