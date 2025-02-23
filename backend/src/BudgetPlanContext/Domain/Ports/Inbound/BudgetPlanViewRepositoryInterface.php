<?php

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanViewRepositoryInterface
{
    public function save(BudgetPlanViewInterface $budgetPlanView): void;

    public function delete(BudgetPlanViewInterface $budgetPlanView): void;

    public function findOneBy(array $criteria, ?array $orderBy = null): ?BudgetPlanViewInterface;

    public function findOnePlanWithEntriesBy(array $criteria, ?array $orderBy = null): array;

    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): BudgetPlansPaginatedInterface;
}
