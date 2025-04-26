<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\GenerateABudgetPlanCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\BudgetPlanContext\Domain\Builders\BudgetPlanDateRegistryBuilder;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanAlreadyExistsException;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanDateRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\TranslatorInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;

final readonly class GenerateABudgetPlanCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UuidGeneratorInterface $uuidGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(GenerateABudgetPlanCommand $command): void
    {
        try {
            $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());

            if ($aggregate instanceof BudgetPlan) {
                throw new BudgetPlanAlreadyExistsException();
            }

        } catch (EventsNotFoundForAggregateException) {
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
                $this->uuidGenerator,
                $this->translator,
            );
            $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
        }
    }
}
