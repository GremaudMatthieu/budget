<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan;

interface BudgetPlanWantEntryFinder
{
    public function findOneByUuid(string $uuid): ?BudgetPlanWantEntry;

    public function save(BudgetPlanWantEntry $budgetPlanWantEntryView): void;

    public function delete(string $uuid): void;

    public function deleteByBudgetPlanId(string $uuid): void;
}
