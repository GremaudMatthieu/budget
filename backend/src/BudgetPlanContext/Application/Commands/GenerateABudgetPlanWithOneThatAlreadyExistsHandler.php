<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\BudgetPlan;
use App\BudgetPlanContext\Domain\BudgetPlanDateRegistryBuilder;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanAlreadyExists;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanDateRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;

final readonly class GenerateABudgetPlanWithOneThatAlreadyExistsHandler
{
    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    public function __invoke(GenerateABudgetPlanWithOneThatAlreadyExists $command): void
    {
        try {
            $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());

            if ($aggregate instanceof BudgetPlan) {
                throw new BudgetPlanAlreadyExists();
            }

        } catch (EventsNotFoundForAggregate) {
            /** @var BudgetPlan $aggregateToCopy */
            $aggregateToCopy = $this->eventSourcedRepository->get(
                (string) $command->getBudgetPlanIdThatAlreadyExists(),
            );
            $aggregatesToSave = BudgetPlanDateRegistryBuilder::build(
                $this->eventSourcedRepository,
                $this->uuidGenerator,
            )
                ->loadOrCreateRegistry(
                    BudgetPlanDateRegistryId::fromUserIdAndBudgetPlanDate(
                        $command->getUserId(),
                        $command->getDate(),
                        $this->uuidGenerator,
                    ),
                )
                ->ensureDateIsAvailable($command->getDate(), $command->getUserId())
                ->registerDate(
                    $command->getDate(),
                    $command->getUserId(),
                    $command->getBudgetPlanId(),
                )
                ->getRegistryAggregates();
            $aggregatesToSave[] = BudgetPlan::createWithOneThatAlreadyExists(
                $command->getBudgetPlanId(),
                $command->getDate(),
                $command->getUserId(),
                $aggregateToCopy,
                $command->getContext(),
                $this->uuidGenerator,
            );
            $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
        }
    }
}
