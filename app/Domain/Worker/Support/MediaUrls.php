<?php

namespace App\Domain\Worker\Support;

use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Short-lived signed links to private media. The route still checks the viewer's policy,
 * so a leaked link is useless to anyone else.
 */
final class MediaUrls
{
    public const TTL_MINUTES = 15;

    public static function signed(?Media $media): ?string
    {
        if ($media === null) {
            return null;
        }

        return URL::temporarySignedRoute('media.show', now()->addMinutes(self::TTL_MINUTES), ['media' => $media->uuid]);
    }
}
