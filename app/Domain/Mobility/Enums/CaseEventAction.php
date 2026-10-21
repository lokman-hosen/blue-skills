<?php

namespace App\Domain\Mobility\Enums;

/**
 * What happened on a mobility case; the history both sides see (spec §6).
 */
enum CaseEventAction: string
{
    case Opened = 'opened';
    case StageStarted = 'stage_started';
    case StageCompleted = 'stage_completed';
    case StageBlocked = 'stage_blocked';
    case StageSkipped = 'stage_skipped';
    case StageResumed = 'stage_resumed';
    case NextActionSet = 'next_action_set';
    case DocumentRequested = 'document_requested';
    case DocumentUploaded = 'document_uploaded';
    case DocumentAccepted = 'document_accepted';
    case DocumentReturned = 'document_returned';
    case DocumentRevoked = 'document_revoked';
    case ContractConfirmed = 'contract_confirmed';
    case Closed = 'closed';
}
