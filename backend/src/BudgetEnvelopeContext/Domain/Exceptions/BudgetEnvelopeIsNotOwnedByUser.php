<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Exceptions;

final class BudgetEnvelopeIsNotOwnedByUser extends \LogicException
{
    private function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function isNotOwnedByUser(): self
    {
        return new self(
            'envelopes.notOwner',
            400,
        );
    }
}
