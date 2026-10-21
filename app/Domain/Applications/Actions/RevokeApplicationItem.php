<?php

namespace App\Domain\Applications\Actions;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The worker stops sharing one item with an application (product rule 6). The consent row is
 * kept with its revoked time; the organization stops seeing the item straight away. The Skill
 * Passport snapshot is the application itself, so it cannot be revoked.
 */
class RevokeApplicationItem
{
    public function handle(Application $application, ApplicationConsentItem $item): void
    {
        $consent = $application->consents()->active()->where('item', $item)->first();

        if ($consent === null || $item === ApplicationConsentItem::SkillPassport) {
            throw ValidationException::withMessages(['item' => __('applications.consent.not_revocable')]);
        }

        DB::transaction(function () use ($application, $consent, $item): void {
            $now = now();
            $consent->update(['revoked_at' => $now]);

            if ($item === ApplicationConsentItem::PassportScan) {
                $application->workerProfile->consents()->active()
                    ->where('purpose', ConsentPurpose::DocumentShare)
                    ->where('subject_type', $application->getMorphClass())
                    ->where('subject_id', $application->id)
                    ->update(['revoked_at' => $now]);
            }

            activity('applications')->performedOn($application)->causedBy($application->workerProfile->user)
                ->withProperties(['item' => $item->value])
                ->log('application.item_revoked');
        });
    }
}
