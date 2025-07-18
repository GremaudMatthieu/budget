<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class RemoveABudgetPlanWantCommand implements CommandInterface
{
    private string $budgetPlanId;
    private string $budgetPlanUserId;
    private string $entryId;

    public function __construct(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $entryId,
        UserId $budgetPlanUserId,
    ) {
        $this->budgetPlanId = (string) $budgetPlanId;
        $this->entryId = (string) $entryId;
        $this->budgetPlanUserId = (string) $budgetPlanUserId;
    }

    public function getBudgetPlanId(): BudgetPlanId
    {
        return BudgetPlanId::fromString($this->budgetPlanId);
    }

    public function getEntryId(): BudgetPlanEntryId
    {
        return BudgetPlanEntryId::fromString($this->entryId);
    }

    public function getBudgetPlanUserId(): UserId
    {
        return UserId::fromString($this->budgetPlanUserId);
    }
}
