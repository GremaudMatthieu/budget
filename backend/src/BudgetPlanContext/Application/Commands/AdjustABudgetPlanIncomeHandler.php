<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;

final readonly class AdjustABudgetPlanIncomeHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(AdjustABudgetPlanIncome $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->adjustAnIncome(
            $command->getBudgetPlanId(),
            $command->getEntryId(),
            $command->getName(),
            $command->getAmount(),
            $command->getCategory(),
            $command->getUserId(),
        );
    }
}
