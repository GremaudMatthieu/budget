<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanWantEntryViewRepositoryInterface
{
    public function save(BudgetPlanWantEntryViewInterface $budgetPlanWantEntryView): void;

    public function delete(string $budgetPlanWantEntryUuid): void;
}
