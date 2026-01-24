<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Exceptions;

final class BudgetPlanIsNotOwnedByUser extends \LogicException
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
            'budgetPlan.notOwner',
            400,
        );
    }
}
