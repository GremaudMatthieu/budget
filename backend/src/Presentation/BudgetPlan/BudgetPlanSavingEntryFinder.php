<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan;

interface BudgetPlanSavingEntryFinder
{
    public function findOneByUuid(string $uuid): ?BudgetPlanSavingEntry;

    public function save(BudgetPlanSavingEntry $budgetPlanSavingEntryView): void;

    public function delete(string $uuid): void;

    public function deleteByBudgetPlanId(string $uuid): void;
}
