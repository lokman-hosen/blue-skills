<?php

namespace App\Domain\Mobility\Enums;

enum CaseDocumentStatus: string
{
    case Requested = 'requested';
    case Uploaded = 'uploaded';
    case Accepted = 'accepted';
    case Returned = 'returned';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Needed',
            self::Uploaded => 'Waiting for check',
            self::Accepted => 'Accepted',
            self::Returned => 'Sent back',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'warn',
            self::Uploaded => 'stamp',
            self::Accepted => 'ok',
            self::Returned => 'bad',
        };
    }
}
