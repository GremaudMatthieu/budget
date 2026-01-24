<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\BudgetPlan;
use App\BudgetPlanContext\Domain\BudgetPlanDateRegistryBuilder;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanDateRegistryId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;

final readonly class RemoveABudgetPlanHandler
{
    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    public function __invoke(RemoveABudgetPlan $command): void
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
