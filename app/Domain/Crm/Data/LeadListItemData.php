<?php

namespace App\Domain\Crm\Data;

use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Lead;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * A lead in the CRM list. Never carries the phone number (D-095).
 */
class LeadListItemData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $signedUp,
        public LeadSource $source,
        public ?string $campaign,
        public ?string $countryCode,
        /** Translated via `status.follow_up_{value}`. */
        public StatusData $followUp,
        /** Translated via `status.lead_stage_{value}`. */
        public StatusData $stage,
        public ?string $assignee,
        public ?string $nextFollowUpAt,
        public bool $overdue,
        public ?string $lastContactedAt,
        public string $createdAt,
    ) {}

    public static function fromLead(Lead $lead): self
    {
        return new self(
            id: $lead->id,
            name: $lead->displayName(),
            signedUp: $lead->user_id !== null,
            source: $lead->source,
            campaign: $lead->campaign?->name,
            countryCode: $lead->country_code,
            followUp: self::followUp($lead),
            stage: self::stage($lead),
            assignee: $lead->assignee?->name,
            nextFollowUpAt: $lead->next_follow_up_at?->toIso8601String(),
            overdue: $lead->next_follow_up_at !== null && $lead->next_follow_up_at->isPast(),
            lastContactedAt: $lead->last_contacted_at?->toIso8601String(),
            createdAt: $lead->created_at?->toIso8601String() ?? '',
        );
    }

    public static function followUp(Lead $lead): StatusData
    {
        $status = $lead->follow_up_status;

        return new StatusData('follow_up_'.$status->value, $status->label(), $status->tone());
    }

    public static function stage(Lead $lead): StatusData
    {
        $stage = $lead->conversion_stage;

        return new StatusData('lead_stage_'.$stage->value, $stage->label(), $stage->tone());
    }
}
