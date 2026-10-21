<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use Illuminate\Validation\ValidationException;

/**
 * The named employer confirms that the agency recruits for it, or declines (D-074). Confirmed
 * mandates go to staff review; declined ones end there.
 */
class RespondToMandate
{
    public function handle(JobMandate $mandate, bool $confirm, ?string $note, User $actor): void
    {
        if ($mandate->status !== MandateStatus::AwaitingEmployer) {
            throw ValidationException::withMessages(['mandate' => __('mandates.already_answered')]);
        }

        $mandate->update([
            'status' => $confirm ? MandateStatus::Pending : MandateStatus::Declined,
            'employer_responded_by' => $actor->id,
            'employer_responded_at' => now(),
            'employer_note' => $note,
        ]);

        activity('mandates')->performedOn($mandate)->causedBy($actor)
            ->withProperties(['confirmed' => $confirm, 'note' => $note])
            ->log($confirm ? 'mandate.confirmed_by_employer' : 'mandate.declined_by_employer');

        foreach (Notify::members($mandate->agency, Permission::MandatesManage) as $member) {
            Notify::user($member, NotificationEvent::MandateAnswered, [
                'employer' => $mandate->employer->display_name,
                'reference' => $mandate->reference,
                'mandate_answer' => $confirm ? 'confirmed' : 'declined',
            ], route('org.mandates.show', $mandate, false));
        }
    }
}
