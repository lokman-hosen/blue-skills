<?php

namespace App\Domain\Messaging\Enums;

/**
 * Who a message is from. Organization members write as their organization; workers see the
 * organization's name and the member's name, never a personal phone number.
 */
enum MessageSide: string
{
    case Worker = 'worker';
    case Organization = 'organization';
    case System = 'system';
}
