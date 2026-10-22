<?php

namespace App\Domain\Institute\Support;

use App\Domain\Institute\Models\Cohort;
use App\Support\QrCode;

/**
 * The ways an institute shares a cohort (D-082): the link, a QR code for posters and classrooms,
 * and a WhatsApp message it sends itself. BLUESKILLS sends nothing to people who haven't joined.
 */
final class CohortInvite
{
    public static function url(Cohort $cohort): string
    {
        return route('cohort-invite.show', $cohort->invite_code);
    }

    public static function whatsappText(Cohort $cohort): string
    {
        return __('cohorts.invite.whatsapp_text', [
            'institute' => (string) $cohort->organization->display_name,
            'course' => $cohort->course->title,
            'url' => self::url($cohort),
        ]);
    }

    public static function qrSvg(Cohort $cohort, int $size = 240): string
    {
        return QrCode::svg(self::url($cohort), $size);
    }
}
