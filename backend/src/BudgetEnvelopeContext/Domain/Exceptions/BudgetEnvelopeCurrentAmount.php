<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Exceptions;

final class BudgetEnvelopeCurrentAmount extends \LogicException
{
    private function __construct(
        string $message,
        int $code,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function exceedsCreditLimit(): self
    {
        return new self(
            'envelopes.exceedsCreditLimit',
            400,
        );
    }

    public static function exceedsDebitLimit(): self
    {
        return new self(
            'envelopes.exceedsDebitLimit',
            400,
        );
    }
}
