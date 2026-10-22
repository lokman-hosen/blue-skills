<?php

namespace App\Domain\Crm\Data;

use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadContactAttempt;
use App\Domain\Crm\Support\LeadProgress;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * One lead for the CRM agent: who, where from, how far they've come and every contact attempt.
 * The phone number is left out once the person asked not to be contacted (D-095).
 */
class LeadData extends Data
{
    /**
     * @param  list<array{stage: ConversionStage, reached: bool}>  $progress
     * @param  list<ContactAttemptData>  $attempts
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $signedUp,
        public ?string $passportReference,
        public ?string $phone,
        public bool $optedOut,
        public LeadSource $source,
        public ?string $campaign,
        public ?string $partner,
        public ?string $countryCode,
        public ?string $assigneeId,
        public FollowUpStatus $followUpStatus,
        public StatusData $followUp,
        public StatusData $stage,
        public ?string $nextFollowUpAt,
        public ?string $lastContactedAt,
        public string $createdAt,
        public array $progress,
        public array $attempts,
    ) {}

    public static function fromLead(Lead $lead): self
    {
        $lead->loadMissing(['user.workerProfile', 'campaign', 'partner', 'contactAttempts.staff']);
        $profile = $lead->user?->workerProfile;
        $reached = LeadProgress::reached($lead->user);

        return new self(
            id: $lead->id,
            name: $lead->displayName(),
            signedUp: $lead->user_id !== null,
            passportReference: $profile?->reference,
            phone: $lead->mayBeContacted() ? $lead->phoneNumber() : null,
            optedOut: $profile?->staff_contact_opted_out_at !== null,
            source: $lead->source,
            campaign: $lead->campaign?->name,
            partner: $lead->partner?->display_name,
            countryCode: $lead->country_code,
            assigneeId: $lead->assigned_to,
            followUpStatus: $lead->follow_up_status,
            followUp: LeadListItemData::followUp($lead),
            stage: LeadListItemData::stage($lead),
            nextFollowUpAt: $lead->next_follow_up_at?->toIso8601String(),
            lastContactedAt: $lead->last_contacted_at?->toIso8601String(),
            createdAt: $lead->created_at?->toIso8601String() ?? '',
            progress: array_values(array_map(
                fn (ConversionStage $stage) => ['stage' => $stage, 'reached' => $reached[$stage->value]],
                array_filter(ConversionStage::cases(), fn (ConversionStage $stage) => $stage !== ConversionStage::Prospect),
            )),
            attempts: $lead->contactAttempts->map(fn (LeadContactAttempt $attempt) => ContactAttemptData::fromAttempt($attempt))->values()->all(),
        );
    }
}
