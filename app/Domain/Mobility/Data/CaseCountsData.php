<?php

namespace App\Domain\Mobility\Data;

use App\Domain\Mobility\States\MobilityCase\Open;
use App\Domain\Mobility\Support\CaseQuery;
use App\Domain\Organization\Models\Organization;
use Spatie\LaravelData\Data;

/**
 * Dashboard tiles for an organization's mobility cases (D-080).
 */
class CaseCountsData extends Data
{
    public function __construct(
        public int $open,
        public int $documentsToCheck,
        public int $overdue,
        public int $onHold,
    ) {}

    public static function forOrganization(Organization $organization): self
    {
        return new self(
            open: CaseQuery::for($organization)->whereState('status', Open::class)->count(),
            documentsToCheck: CaseQuery::needing(CaseQuery::for($organization), 'documents')->count(),
            overdue: CaseQuery::needing(CaseQuery::for($organization), 'overdue')->count(),
            onHold: CaseQuery::needing(CaseQuery::for($organization), 'on_hold')->count(),
        );
    }
}
