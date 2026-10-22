<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR codes for invite and campaign links on posters and slides.
 */
final class QrCode
{
    /**
     * Black on white so it scans when printed, whatever the page theme. Without the XML
     * declaration so the markup can sit inside the page.
     */
    public static function svg(string $url, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle($size, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(0, 0, 0))),
            new SvgImageBackEnd,
        ));

        $svg = $writer->writeString($url);

        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }
}
