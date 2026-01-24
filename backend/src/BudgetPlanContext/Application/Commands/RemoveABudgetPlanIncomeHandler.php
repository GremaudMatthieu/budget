<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;

final readonly class RemoveABudgetPlanIncomeHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(RemoveABudgetPlanIncome $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->removeAnIncome($command->getEntryId(), $command->getBudgetPlanUserId());
    }
}
