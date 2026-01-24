<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;

final readonly class RemoveABudgetPlanWantHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(RemoveABudgetPlanWant $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->removeAWant($command->getEntryId(), $command->getBudgetPlanUserId());
    }
}
