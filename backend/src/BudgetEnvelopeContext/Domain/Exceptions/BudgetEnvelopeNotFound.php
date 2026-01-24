<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Exceptions;

final class BudgetEnvelopeNotFound extends \Exception
{
    public const string MESSAGE = 'envelopes.notFound';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 404,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
