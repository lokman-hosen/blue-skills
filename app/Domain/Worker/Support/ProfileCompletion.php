<?php

namespace App\Domain\Worker\Support;

use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Models\WorkerProfile;

/**
 * Progressive onboarding (spec §3): phone → trade → profile answers → SkillShow → evidence.
 * Nothing here blocks the worker from using the portal; it only suggests the next step.
 */
class ProfileCompletion
{
    public const STEPS = ['phone', 'trade', 'answers', 'skillshow', 'evidence'];

    /**
     * @return array<string, bool>
     */
    public function steps(WorkerProfile $profile): array
    {
        $profile->loadMissing('user');

        return [
            'phone' => $profile->user->phone_verified_at !== null,
            'trade' => $profile->occupation_id !== null,
            'answers' => $this->hasCoreAnswers($profile),
            'skillshow' => $profile->skillShows()->where('moderation_status', '!=', ModerationStatus::Failed)->exists(),
            'evidence' => $profile->evidenceItems()->exists(),
        ];
    }

    /**
     * The seven Voice-to-CV fields, however they were entered (voice, typing or editing).
     */
    public function hasCoreAnswers(WorkerProfile $profile): bool
    {
        return $profile->occupation_id !== null
            && $profile->years_experience !== null
            && $profile->passport_status !== null
            && $profile->availability !== null
            && $profile->experiences()->exists()
            && $profile->workerSkills()->exists()
            && $profile->destinationPreferences()->exists();
    }

    /**
     * Stamps `profile_completed_at` the first time the core answers are all present.
     */
    public function refresh(WorkerProfile $profile): void
    {
        if ($profile->profile_completed_at === null && $this->hasCoreAnswers($profile)) {
            $profile->forceFill(['profile_completed_at' => now()])->save();
        }
    }
}
