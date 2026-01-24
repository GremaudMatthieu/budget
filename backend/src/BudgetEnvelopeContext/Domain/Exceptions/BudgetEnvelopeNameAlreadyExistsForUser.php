<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Exceptions;

final class BudgetEnvelopeNameAlreadyExistsForUser extends \LogicException
{
    public const string MESSAGE = 'envelopes.nameAlreadyExistsForUser';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
