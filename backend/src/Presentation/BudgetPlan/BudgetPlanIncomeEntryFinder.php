<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan;

interface BudgetPlanIncomeEntryFinder
{
    public function findOneByUuid(string $uuid): ?BudgetPlanIncomeEntry;

    public function save(BudgetPlanIncomeEntry $budgetPlanIncomeEntryView): void;

    public function delete(string $uuid): void;

    public function deleteByBudgetPlanId(string $uuid): void;
}
