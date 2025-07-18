<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanCurrency;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class ChangeABudgetPlanCurrencyCommand implements CommandInterface
{
    private string $budgetPlanCurrency;
    private string $budgetPlanId;
    private string $budgetPlanUserId;

    public function __construct(
        BudgetPlanCurrency $budgetPlanCurrency,
        BudgetPlanId $budgetPlanId,
        UserId $budgetPlanUserId,
    ) {
        $this->budgetPlanCurrency = (string) $budgetPlanCurrency;
        $this->budgetPlanId = (string) $budgetPlanId;
        $this->budgetPlanUserId = (string) $budgetPlanUserId;
    }

    public function getBudgetPlanCurrency(): BudgetPlanCurrency
    {
        return BudgetPlanCurrency::fromString($this->budgetPlanCurrency);
    }

    public function getBudgetPlanUserId(): UserId
    {
        return UserId::fromString($this->budgetPlanUserId);
    }

    public function getBudgetPlanId(): BudgetPlanId
    {
        return BudgetPlanId::fromString($this->budgetPlanId);
    }
}
