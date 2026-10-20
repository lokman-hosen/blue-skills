<?php

namespace App\Domain\Applications\Enums;

enum ConsentPurpose: string
{
    case Discovery = 'discovery';
    case ApplicationShare = 'application_share';
    case DocumentShare = 'document_share';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Discovery => 'Discovery',
            self::ApplicationShare => 'Application share',
            self::DocumentShare => 'Document share',
        };
    }
}
