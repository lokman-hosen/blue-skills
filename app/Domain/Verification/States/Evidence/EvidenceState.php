<?php

namespace App\Domain\Verification\States\Evidence;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Model>
 */
abstract class EvidenceState extends State
{
    abstract public function label(): string;

    /**
     * Design-system tone used by StatusChip: ok, warn, bad, brand, stamp or neutral.
     */
    abstract public function tone(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(SelfDeclared::class)
            ->allowTransition(SelfDeclared::class, UploadedUnreviewed::class)
            ->allowTransition(SelfDeclared::class, VerificationPending::class)
            ->allowTransition(UploadedUnreviewed::class, VerificationPending::class)
            ->allowTransition(VerificationPending::class, IssuerVerified::class)
            ->allowTransition(VerificationPending::class, Rejected::class)
            ->allowTransition(IssuerVerified::class, Expired::class)
            ->allowTransition(Expired::class, UploadedUnreviewed::class)
            ->allowTransition(Rejected::class, UploadedUnreviewed::class);
    }
}
