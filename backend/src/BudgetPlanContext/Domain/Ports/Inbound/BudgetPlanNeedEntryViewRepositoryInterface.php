<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanNeedEntryViewRepositoryInterface
{
    public function save(BudgetPlanNeedEntryViewInterface $budgetPlanNeedEntryView): void;

    public function delete(string $budgetPlanNeedEntryUuid): void;
}
