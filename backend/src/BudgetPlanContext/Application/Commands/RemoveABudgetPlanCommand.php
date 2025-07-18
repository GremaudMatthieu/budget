<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class RemoveABudgetPlanCommand implements CommandInterface
{
    private string $budgetPlanId;
    private string $budgetPlanUserId;

    public function __construct(
        BudgetPlanId $budgetPlanId,
        UserId $budgetPlanUserId,
    ) {
        $this->budgetPlanId = (string) $budgetPlanId;
        $this->budgetPlanUserId = (string) $budgetPlanUserId;
    }

    public function getBudgetPlanId(): BudgetPlanId
    {
        return BudgetPlanId::fromString($this->budgetPlanId);
    }

    public function getBudgetPlanUserId(): UserId
    {
        return UserId::fromString($this->budgetPlanUserId);
    }
}
