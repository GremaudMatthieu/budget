<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Exceptions;

final class BudgetPlanAlreadyExistsException extends \LogicException
{
    public const string MESSAGE = 'budgetPlan.alreadyExists';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
