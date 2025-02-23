<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlansPaginatedInterface;

final class BudgetPlansPaginated implements BudgetPlansPaginatedInterface, \jsonSerializable
{
    /* @var array<object> */
    private(set) iterable $budgetPlans;
    private(set) int $totalItems;

    /**
     * @param array<object> $budgetPlans
     */
    public function __construct(iterable $budgetPlans, int $totalItems)
    {
        $this->budgetPlans = $budgetPlans;
        $this->totalItems = $totalItems;
    }

    public function jsonSerialize(): array
    {
        return [
            'budgetPlans' => $this->budgetPlans,
            'totalItems' => $this->totalItems,
        ];
    }
}
