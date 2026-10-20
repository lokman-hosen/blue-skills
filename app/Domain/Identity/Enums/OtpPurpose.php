<?php

namespace App\Domain\Identity\Enums;

enum OtpPurpose: string
{
    case Login = 'login';
    /** A worker confirming account deletion with a fresh code (D-044). */
    case AccountDeletion = 'account_deletion';
}
