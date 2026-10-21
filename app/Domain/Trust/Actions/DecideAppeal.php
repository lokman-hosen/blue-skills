<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Organization\Models\Organization;
use App\Domain\Trust\Enums\AppealStatus;
use App\Domain\Trust\Enums\TrustActionType;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\Models\Appeal;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;

/**
 * Another trust officer decides an appeal (D-088): upheld keeps the suspension, overturned lifts
 * it with a restore action. The appellant is told either way.
 */
class DecideAppeal
{
    public function __construct(private readonly SetSuspension $suspension) {}

    public function handle(Appeal $appeal, bool $overturn, string $note, User $actor): void
    {
        DB::transaction(function () use ($appeal, $overturn, $note, $actor): void {
            $appeal->update([
                'status' => $overturn ? AppealStatus::Overturned : AppealStatus::Upheld,
                'decided_by' => $actor->id,
                'decision_note' => $note,
                'decided_at' => now(),
            ]);

            $subject = $appeal->suspension->subject;

            if ($overturn && ($subject instanceof Organization || $subject instanceof JobPosting || $subject instanceof WorkerProfile)) {
                $this->suspension->handle($subject, TrustActionType::Restore, TrustReason::AppealOverturned, $note, $actor, $appeal->suspension->trustCase);
            }

            activity('trust')->performedOn($appeal)->causedBy($actor)
                ->withProperties(['status' => $appeal->status->value, 'note' => $note])
                ->log('appeal.decided');
        });

        if ($appeal->appellant !== null) {
            Notify::user($appeal->appellant, NotificationEvent::AppealDecided, [
                'appeal_status' => $appeal->status->value,
            ], route('appeals.index', absolute: false));
        }
    }
}
