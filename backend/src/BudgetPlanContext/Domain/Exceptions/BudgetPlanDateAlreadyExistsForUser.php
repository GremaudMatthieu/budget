<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Exceptions;

final class BudgetPlanDateAlreadyExistsForUser extends \LogicException
{
    public const string MESSAGE = 'budgetPlan.dateAlreadyExistsForUser';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
