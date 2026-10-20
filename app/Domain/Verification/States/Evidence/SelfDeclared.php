<?php

namespace App\Domain\Verification\States\Evidence;

class SelfDeclared extends EvidenceState
{
    /** @var string */
    public static $name = 'self_declared';

    public function label(): string
    {
        return 'Self-declared';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
