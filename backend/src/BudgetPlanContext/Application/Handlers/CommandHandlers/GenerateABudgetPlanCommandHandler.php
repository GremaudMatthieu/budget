<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\GenerateABudgetPlanCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanAlreadyExistsException;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;

final readonly class GenerateABudgetPlanCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function __invoke(GenerateABudgetPlanCommand $generateABudgetPlanCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $generateABudgetPlanCommand->getBudgetPlanId());

        if ($events->current()) {
            throw new BudgetPlanAlreadyExistsException();
        }

        $aggregate = BudgetPlan::create(
            $generateABudgetPlanCommand->getBudgetPlanId(),
            $generateABudgetPlanCommand->getDate(),
            $generateABudgetPlanCommand->getIncomes(),
            $generateABudgetPlanCommand->getUserId(),
            $this->budgetPlanViewRepository,
            $this->uuidGenerator,
        );

        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), 0);
        $aggregate->clearRaisedDomainEvents();
    }
}
