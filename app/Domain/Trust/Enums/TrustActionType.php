<?php

namespace App\Domain\Trust\Enums;

enum TrustActionType: string
{
    case Suspend = 'suspend';
    case Restore = 'restore';
}
