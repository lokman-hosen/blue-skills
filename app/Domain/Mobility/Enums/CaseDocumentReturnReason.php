<?php

namespace App\Domain\Mobility\Enums;

/**
 * Why the managing organization sends a worker's case document back (D-079). The worker sees
 * the reason with how to fix it.
 */
enum CaseDocumentReturnReason: string
{
    case Unreadable = 'unreadable';
    case WrongDocument = 'wrong_document';
    case Expired = 'expired';
    case Incomplete = 'incomplete';
    case DetailsMismatch = 'details_mismatch';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unreadable => 'Hard to read',
            self::WrongDocument => 'Not the document asked for',
            self::Expired => 'Expired',
            self::Incomplete => 'Pages or details missing',
            self::DetailsMismatch => 'Details do not match your passport',
            self::Other => 'Other',
        };
    }
}
