<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Exceptions;

final class BudgetEnvelopeTargetedAmount extends \LogicException
{
    private function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function isBelowZero(): self
    {
        return new self(
            'envelopes.targetedAmountIsBelowZero',
            400,
        );
    }

    public static function isBelowCurrentAmount(): self
    {
        return new self(
            'envelopes.targetedAmountIsBelowCurrentAmount',
            400,
        );
    }
}
