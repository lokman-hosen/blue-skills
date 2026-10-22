<?php

namespace App\Domain\Billing\Policies;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Support\OrganizationPermissions;

/**
 * Finance staff (`finance.manage`) issue and settle invoices. An organization's billing managers
 * (`billing.manage`) see their own invoices once issued; drafts stay with finance (D-095).
 */
class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasStaffPermission(Permission::FinanceManage->value);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return ! $invoice->isDraft()
            && OrganizationPermissions::allows($user, $invoice->organization, Permission::BillingManage);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->viewAny($user);
    }
}
