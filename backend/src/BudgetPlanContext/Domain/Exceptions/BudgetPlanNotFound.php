<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Exceptions;

final class BudgetPlanNotFound extends \Exception
{
    public const string MESSAGE = 'budgetPlan.notFound';

    public function __construct(
        string $message = self::MESSAGE,
        int $code = 404,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
