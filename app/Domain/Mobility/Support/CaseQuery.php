<?php

namespace App\Domain\Mobility\Support;

use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Open;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

/**
 * Case list queries for an organization's workspace (D-080): the cases it runs or employs on,
 * narrowed to what needs attention.
 */
final class CaseQuery
{
    public const ATTENTION = ['documents', 'overdue', 'on_hold'];

    /**
     * @return Builder<MobilityCase>
     */
    public static function for(Organization $organization): Builder
    {
        return MobilityCase::query()->where(fn (Builder $query) => $query
            ->where('managing_organization_id', $organization->id)
            ->orWhere('employer_organization_id', $organization->id));
    }

    /**
     * Shared documents wait for the organization running the case, so only its own cases count
     * for "documents"; an employer following an agency case still sees overdue and held stages.
     *
     * @param  Builder<MobilityCase>  $query
     * @return Builder<MobilityCase>
     */
    public static function needing(Builder $query, string $attention, Organization $organization): Builder
    {
        $query->whereState('status', Open::class);

        return match ($attention) {
            'documents' => $query->where('managing_organization_id', $organization->id)->whereHas('documents', fn (Builder $documents) => $documents
                ->where('provided_by', DocumentProvider::Worker)
                ->where('status', CaseDocumentStatus::Uploaded)
                ->whereNotNull('shared_at')
                ->whereNull('revoked_at')),
            'overdue' => $query->whereHas('stages', fn (Builder $stages) => $stages
                ->whereColumn('mobility_case_stages.key', 'mobility_cases.current_stage')
                ->whereDate('due_on', '<', now()->toDateString())),
            'on_hold' => $query->whereHas('stages', fn (Builder $stages) => $stages
                ->whereColumn('mobility_case_stages.key', 'mobility_cases.current_stage')
                ->where('status', MobilityStageStatus::Blocked)),
            default => $query,
        };
    }
}
