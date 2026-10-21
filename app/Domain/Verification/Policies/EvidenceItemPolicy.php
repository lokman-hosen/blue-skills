<?php

namespace App\Domain\Verification\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\VerificationPending;

/**
 * Verification Officers and Superusers review worker evidence (D-088, D-090).
 */
class EvidenceItemPolicy
{
    public function review(User $user, ?EvidenceItem $item = null): bool
    {
        return $user->hasStaffPermission(Permission::VerificationsReview->value);
    }

    /**
     * Staff open the file only after starting a review, while it is pending (D-088).
     */
    public function viewFile(User $user, EvidenceItem $item): bool
    {
        return $this->review($user) && $item->status->equals(VerificationPending::class);
    }
}
