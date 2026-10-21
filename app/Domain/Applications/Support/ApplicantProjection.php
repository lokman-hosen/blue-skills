<?php

namespace App\Domain\Applications\Support;

use App\Domain\Applications\Data\ProfileSnapshotData;
use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Models\SkillShow;

/**
 * What an organization may see of one applicant right now: the dated snapshot, minus anything
 * the worker has since stopped sharing, plus live items (phone, scan, SkillShow) only while their
 * consent is active (product rules 1, 6 and 7).
 */
final class ApplicantProjection
{
    /** @var array<string, ApplicationConsent> */
    private array $active;

    public function __construct(private readonly Application $application)
    {
        $application->loadMissing(['consents', 'snapshot', 'skillShow', 'workerProfile.user']);
        $this->active = $application->consents
            ->filter(fn (ApplicationConsent $consent) => $consent->isActive())
            ->keyBy(fn (ApplicationConsent $consent) => $consent->item->value)
            ->all();
    }

    public function shares(ApplicationConsentItem $item): bool
    {
        return array_key_exists($item->value, $this->active);
    }

    public function snapshot(): ?ProfileSnapshotData
    {
        $data = $this->application->snapshot?->data;

        if ($data === null) {
            return null;
        }

        if (! $this->shares(ApplicationConsentItem::WorkExperience)) {
            $data['experiences'] = [];
        }

        if (! $this->shares(ApplicationConsentItem::TrainingCertificates)) {
            $data['credentials'] = [];
        }

        return ProfileSnapshotData::from($data);
    }

    public function skillShow(): ?SkillShow
    {
        $skillShow = $this->application->skillShow;

        return $this->shares(ApplicationConsentItem::SkillShow) && $skillShow !== null && $skillShow->isPublished() ? $skillShow : null;
    }

    public function phone(): ?string
    {
        return $this->shares(ApplicationConsentItem::PhoneNumber) ? (string) $this->application->workerProfile->user->phone : null;
    }

    public function passportScan(): ?Document
    {
        return $this->shares(ApplicationConsentItem::PassportScan) ? $this->active[ApplicationConsentItem::PassportScan->value]->document : null;
    }
}
