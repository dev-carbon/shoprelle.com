<?php

namespace App\Exceptions;

use App\Enums\PurchaseRequestStatus;
use DomainException;

/**
 * Raised when an administrator attempts a status change the lifecycle forbids.
 */
class InvalidStatusTransition extends DomainException
{
    public static function between(PurchaseRequestStatus $from, PurchaseRequestStatus $to): self
    {
        return new self(sprintf(
            'Une demande « %s » ne peut pas passer à « %s ».',
            $from->label(),
            $to->label(),
        ));
    }
}
