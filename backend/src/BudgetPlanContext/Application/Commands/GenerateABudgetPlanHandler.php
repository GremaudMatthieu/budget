<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\BudgetPlan;
use App\BudgetPlanContext\Domain\BudgetPlanDateRegistryBuilder;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanAlreadyExists;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanDateRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\Translator;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;

final readonly class GenerateABudgetPlanHandler
{
    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private UuidGenerator $uuidGenerator,
        private Translator $translator,
    ) {
    }

    public function __invoke(GenerateABudgetPlan $command): void
    {
        try {
            $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());

            if ($aggregate instanceof BudgetPlan) {
                throw new BudgetPlanAlreadyExists();
            }

        } catch (EventsNotFoundForAggregate) {
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
            $aggregatesToSave[] = BudgetPlan::create(
                $command->getBudgetPlanId(),
                $command->getDate(),
                $command->getIncomes(),
                $command->getUserId(),
                $command->getUserLanguagePreference(),
                $command->getCurrency(),
                $command->getContext(),
                $this->uuidGenerator,
                $this->translator,
            );
            $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
        }
    }
}
