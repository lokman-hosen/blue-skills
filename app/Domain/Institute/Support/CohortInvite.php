<?php

namespace App\Domain\Institute\Support;

use App\Domain\Institute\Models\Cohort;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

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

    /**
     * Black on white so it scans when printed, whatever the page theme.
     */
    public static function qrSvg(Cohort $cohort, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle($size, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(0, 0, 0))),
            new SvgImageBackEnd,
        ));

        $svg = $writer->writeString(self::url($cohort));

        // Drop the XML declaration so the markup can sit inside the page.
        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }
}
