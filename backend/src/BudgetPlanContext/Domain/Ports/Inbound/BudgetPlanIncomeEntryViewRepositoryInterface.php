<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanIncomeEntryViewRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?BudgetPlanIncomeEntryViewInterface;

    public function save(BudgetPlanIncomeEntryViewInterface $budgetPlanIncomeEntryView): void;

    public function delete(string $uuid): void;

    public function deleteByBudgetPlanId(string $uuid): void;
}
