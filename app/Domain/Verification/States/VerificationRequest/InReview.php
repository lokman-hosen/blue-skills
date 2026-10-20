<?php

namespace App\Domain\Verification\States\VerificationRequest;

class InReview extends VerificationRequestState
{
    /** @var string */
    public static $name = 'in_review';

    public function label(): string
    {
        return 'In review';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
