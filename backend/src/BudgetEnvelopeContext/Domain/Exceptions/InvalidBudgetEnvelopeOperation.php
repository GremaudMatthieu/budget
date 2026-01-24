<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Exceptions;

final class InvalidBudgetEnvelopeOperation extends \LogicException
{
    private function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function operationOnDeletedEnvelope(): self
    {
        return new self(
            'envelopes.operationOnDeletedEnvelope',
            400,
        );
    }
}
