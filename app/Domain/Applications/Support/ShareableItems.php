<?php

namespace App\Domain\Applications\Support;

use App\Domain\Applications\Data\ShareableItemData;
use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Actions\AddWorkerCredential;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;

/**
 * What a worker can share with an application, and what is on by default (product rule 6).
 */
final class ShareableItems
{
    /**
     * @return array<string, ShareableItemData> keyed by item value, in display order
     */
    public static function for(WorkerProfile $profile): array
    {
        $skillShow = self::skillShow($profile);
        $scan = self::passportScan($profile);
        $experienceCount = $profile->experiences()->count();
        $credentialCount = $profile->evidenceItems()->whereIn('type', AddWorkerCredential::TYPES)->count();
        $phone = (string) $profile->user->phone;

        $items = [
            new ShareableItemData(ApplicationConsentItem::SkillPassport, true, false, true, true, 'skill_passport'),
            $skillShow !== null
                ? new ShareableItemData(ApplicationConsentItem::SkillShow, false, false, true, true, 'skill_show')
                : new ShareableItemData(ApplicationConsentItem::SkillShow, false, false, false, false, 'skill_show_missing'),
            new ShareableItemData(ApplicationConsentItem::WorkExperience, false, false, $experienceCount > 0, $experienceCount > 0, $experienceCount > 0 ? 'work_experience' : 'work_experience_missing', ['count' => $experienceCount]),
            new ShareableItemData(ApplicationConsentItem::TrainingCertificates, false, false, $credentialCount > 0, $credentialCount > 0, $credentialCount > 0 ? 'certificates' : 'certificates_missing', ['count' => $credentialCount]),
            new ShareableItemData(ApplicationConsentItem::PassportScan, false, true, $scan !== null, false, $scan !== null ? 'passport_scan' : 'passport_scan_missing'),
            new ShareableItemData(ApplicationConsentItem::PhoneNumber, false, true, $phone !== '', false, 'phone', ['phone' => self::mask($phone)]),
        ];

        $keyed = [];

        foreach ($items as $item) {
            $keyed[$item->item->value] = $item;
        }

        return $keyed;
    }

    /**
     * Only a SkillShow a moderator approved can be shared (product rule 3).
     */
    public static function skillShow(WorkerProfile $profile): ?SkillShow
    {
        return $profile->skillShows()->get()->first(fn (SkillShow $skillShow) => $skillShow->isPublished());
    }

    /**
     * The worker's current passport scan, from the Documents page.
     */
    public static function passportScan(WorkerProfile $profile): ?Document
    {
        return Document::query()
            ->where('owner_user_id', $profile->user_id)
            ->where('type', DocumentType::Passport)
            ->latest()
            ->first();
    }

    public static function mask(string $phone): string
    {
        return $phone === '' ? '' : substr($phone, 0, 4).str_repeat('•', max(strlen($phone) - 7, 0)).substr($phone, -3);
    }
}
