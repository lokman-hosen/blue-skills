<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Models\CaseDocument;

/**
 * Whether the employer of an agency job sees an item (D-079): the worker decides for their own
 * files. The managing organization always sees what is shared with the case.
 */
class SetCaseDocumentVisibility
{
    public function handle(CaseDocument $item, bool $employerCanView, User $actor): void
    {
        if ($item->employer_can_view === $employerCanView) {
            return;
        }

        $item->update(['employer_can_view' => $employerCanView]);

        activity('mobility')->performedOn($item->mobilityCase)->causedBy($actor)
            ->withProperties(['document' => $item->id, 'old' => ['employer_can_view' => ! $employerCanView], 'attributes' => ['employer_can_view' => $employerCanView]])
            ->log('mobility_case.document_visibility');
    }
}
