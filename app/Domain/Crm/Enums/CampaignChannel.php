<?php

namespace App\Domain\Crm\Enums;

/**
 * Where a campaign reaches people (spec §9).
 */
enum CampaignChannel: string
{
    case Social = 'social';
    case JobFair = 'job_fair';
    case Partner = 'partner';
    case Institute = 'institute';
    case RadioTv = 'radio_tv';
    case SmsWhatsapp = 'sms_whatsapp';
    case Referral = 'referral';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Social => 'Social media',
            self::JobFair => 'Job fair',
            self::Partner => 'Partner',
            self::Institute => 'Institute',
            self::RadioTv => 'Radio or TV',
            self::SmsWhatsapp => 'SMS or WhatsApp',
            self::Referral => 'Referral',
            self::Other => 'Other',
        };
    }
}
