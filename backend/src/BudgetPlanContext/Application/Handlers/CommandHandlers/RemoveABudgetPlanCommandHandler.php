<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\BudgetPlanContext\Domain\Builders\BudgetPlanDateRegistryBuilder;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanDateRegistryId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;

final readonly class RemoveABudgetPlanCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function __invoke(RemoveABudgetPlanCommand $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->remove($command->getBudgetPlanUserId());
        BudgetPlanDateRegistryBuilder::build(
            $this->eventSourcedRepository,
            $this->uuidGenerator,
        )
            ->loadOldRegistry(
                BudgetPlanDateRegistryId::fromUserIdAndBudgetPlanDate(
                    $command->getBudgetPlanUserId(),
                    $aggregate->date,
                    $this->uuidGenerator,
                ),
            )
            ->releaseDate(
                $aggregate->date,
                $command->getBudgetPlanUserId(),
                $command->getBudgetPlanId(),
            );
    }
}
