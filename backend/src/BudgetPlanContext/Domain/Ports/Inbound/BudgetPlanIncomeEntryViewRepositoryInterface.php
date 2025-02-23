<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanIncomeEntryViewRepositoryInterface
{
    public function save(BudgetPlanIncomeEntryViewInterface $budgetPlanIncomeEntryView): void;

    public function delete(string $budgetPlanIncomeEntryUuid): void;
}
