<?php

namespace App\Domain\Mobility\Enums;

/**
 * Who supplies a case document: the worker (medical, police clearance…) or the managing
 * organization (contract, work permit, visa).
 */
enum DocumentProvider: string
{
    case Worker = 'worker';
    case Organization = 'organization';
}
