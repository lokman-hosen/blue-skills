<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Lead;
use App\Domain\Identity\Models\User;

/**
 * Every time a CRM agent opens a lead page showing a phone number, it is written to the audit
 * log (D-095).
 */
class RecordLeadPhoneView
{
    public function handle(Lead $lead, User $agent): void
    {
        if ($lead->mayBeContacted() && $lead->phoneNumber() !== null) {
            activity('crm')->performedOn($lead)->causedBy($agent)->log('lead.phone_viewed');
        }
    }
}
