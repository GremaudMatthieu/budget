<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Queries;

use App\SharedContext\Domain\Ports\Inbound\QueryInterface;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class ListBudgetPlansCalendarQuery implements QueryInterface
{
    private string $budgetPlanUserId;

    public function __construct(
        UserId $budgetPlanUserId,
    ) {
        $this->budgetPlanUserId = (string) $budgetPlanUserId;
    }

    public function getBudgetPlanUserId(): UserId
    {
        return UserId::fromString($this->budgetPlanUserId);
    }
}
