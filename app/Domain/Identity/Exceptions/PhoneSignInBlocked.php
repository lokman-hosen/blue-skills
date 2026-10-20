<?php

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

/**
 * The number belongs to a deleted account or to an organization/staff user (D-037).
 */
class PhoneSignInBlocked extends RuntimeException {}
