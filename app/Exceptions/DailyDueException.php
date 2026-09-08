<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Domain errors raised by DailyDueService, mapped by callers to a form
 * error (HTTP) or an import-row message (Excel import).
 */
class DailyDueException extends RuntimeException
{
    public static function duplicate(): self
    {
        return new self('A due has already been recorded for this member on this date.');
    }

    public static function noTicketsAvailable(): self
    {
        return new self('Not enough available tickets left for this route. Add a new ticket booklet first.');
    }

    public static function ticketAlreadyUsed(string $number): self
    {
        return new self("Ticket #{$number} has already been used.");
    }
}
