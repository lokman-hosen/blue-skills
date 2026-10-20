<?php

namespace App\Domain\Verification\States\Evidence;

class UploadedUnreviewed extends EvidenceState
{
    /** @var string */
    public static $name = 'uploaded_unreviewed';

    public function label(): string
    {
        return 'Uploaded · unreviewed';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
