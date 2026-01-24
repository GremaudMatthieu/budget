<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeNameRegistryBuilder;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeNameRegistryId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;

final readonly class RewindABudgetEnvelopeFromEventsHandler
{
    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    public function __invoke(RewindABudgetEnvelopeFromEvents $command): void
    {
        /** @var BudgetEnvelope $oldAggregate */
        $oldAggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get(
            (string) $command->getBudgetEnvelopeId(),
            $command->getDesiredDateTime(),
        );
        $aggregate->rewind($command->getBudgetEnvelopeUserId(), $command->getDesiredDateTime());
        $oldName = $oldAggregate->getBudgetEnvelopeName();
        $newName = $aggregate->getBudgetEnvelopeName();
        $userId = $command->getBudgetEnvelopeUserId();
        $envelopeId = $command->getBudgetEnvelopeId();
        $aggregatesToSave = [];

        if (!$oldName->equals($newName)) {
            $oldBudgetEnvelopeNameRegistryId = BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                $userId,
                $oldName,
                $this->uuidGenerator,
            );
            $newBudgetEnvelopeNameRegistryId = BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                $userId,
                $newName,
                $this->uuidGenerator,
            );
            $builder = BudgetEnvelopeNameRegistryBuilder::build(
                $this->eventSourcedRepository,
                $this->uuidGenerator
            );
            $builder->loadOldRegistry($oldBudgetEnvelopeNameRegistryId)
                ->releaseName($oldName, $userId, $envelopeId);
            $builder->loadOrCreateRegistry($newBudgetEnvelopeNameRegistryId)
                ->ensureNameIsAvailable($newName, $userId, $envelopeId)
                ->registerName($newName, $userId, $envelopeId);
            $aggregatesToSave = $builder->getRegistryAggregates();
        }

        if (!empty($aggregatesToSave)) {
            $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
        }
    }
}
