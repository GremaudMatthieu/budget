<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Builders\BudgetEnvelopeNameRegistryBuilder;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeAlreadyExistsException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeNameRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;

final readonly class AddABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function __invoke(AddABudgetEnvelopeCommand $command): void
    {
        try {
            $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());

            if ($aggregate instanceof BudgetEnvelope) {
                throw new BudgetEnvelopeAlreadyExistsException();
            }
        } catch (EventsNotFoundForAggregateException) {
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
