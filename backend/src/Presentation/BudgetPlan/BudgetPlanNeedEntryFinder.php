<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan;

interface BudgetPlanNeedEntryFinder
{
    public function findOneByUuid(string $uuid): ?BudgetPlanNeedEntry;

    public function save(BudgetPlanNeedEntry $budgetPlanNeedEntryView): void;

    public function delete(string $uuid): void;

    public function deleteByBudgetPlanId(string $uuid): void;
}
