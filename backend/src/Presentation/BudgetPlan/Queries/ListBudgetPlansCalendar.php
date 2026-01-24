<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\Queries;

use App\SharedContext\Domain\Ports\Inbound\Query;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class ListBudgetPlansCalendar implements Query
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
