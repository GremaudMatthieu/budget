<?php

namespace App\Presentation\BudgetPlan;

interface BudgetPlanFinder
{
    public function save(BudgetPlan $budgetPlanView): void;

    public function delete(BudgetPlan $budgetPlanView): void;

    public function findOneBy(array $criteria, ?array $orderBy = null): ?BudgetPlan;

    public function findOnePlanWithEntriesBy(array $criteria, ?array $orderBy = null): array;

    public function getACalendarWithItsBudgetPlansFinancialRatiosByYear(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array;

    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array;
}
