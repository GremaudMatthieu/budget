<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Exceptions;

final class EventsNotFoundForAggregate extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
