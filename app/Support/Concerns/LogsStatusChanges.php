<?php

namespace App\Support\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Writes every status change to the activity log with before/after values (CLAUDE.md).
 * Actions add the reason code via activity()->withProperties() when they transition.
 */
trait LogsStatusChanges
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->getTable())
            ->logOnly($this->loggedStatusAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status'];
    }
}
