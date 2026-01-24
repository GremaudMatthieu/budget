<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeNameRegistryBuilder;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeAlreadyExists;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeNameRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;

final readonly class AddABudgetEnvelopeHandler
{
    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    public function __invoke(AddABudgetEnvelope $command): void
    {
        try {
            $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());

            if ($aggregate instanceof BudgetEnvelope) {
                throw new BudgetEnvelopeAlreadyExists();
            }
        } catch (EventsNotFoundForAggregate) {
            $aggregatesToSave = BudgetEnvelopeNameRegistryBuilder::build(
                $this->eventSourcedRepository,
                $this->uuidGenerator,
            )
                ->loadOrCreateRegistry(
                    BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                        $command->getBudgetEnvelopeUserId(),
                        $command->getBudgetEnvelopeName(),
                        $this->uuidGenerator,
                    ),
                )
                ->ensureNameIsAvailable($command->getBudgetEnvelopeName(), $command->getBudgetEnvelopeUserId())
                ->registerName(
                    $command->getBudgetEnvelopeName(),
                    $command->getBudgetEnvelopeUserId(),
                    $command->getBudgetEnvelopeId(),
                )
                ->getRegistryAggregates();
            $aggregatesToSave[] = BudgetEnvelope::create(
                $command->getBudgetEnvelopeId(),
                $command->getBudgetEnvelopeUserId(),
                $command->getBudgetEnvelopeTargetedAmount(),
                $command->getBudgetEnvelopeName(),
                $command->getBudgetEnvelopeCurrency(),
                $command->getContext(),
            );
            $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
        }
    }
}
