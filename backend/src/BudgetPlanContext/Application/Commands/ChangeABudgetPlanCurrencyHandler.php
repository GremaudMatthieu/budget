<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;

final readonly class ChangeABudgetPlanCurrencyHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(ChangeABudgetPlanCurrency $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->changeCurrency($command->getBudgetPlanCurrency(), $command->getBudgetPlanUserId());
    }
}
