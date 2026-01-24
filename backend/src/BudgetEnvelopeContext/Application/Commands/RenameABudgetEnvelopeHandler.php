<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeNameRegistryBuilder;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeNameRegistryId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;

final readonly class RenameABudgetEnvelopeHandler
{
    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    public function __invoke(RenameABudgetEnvelope $command): void
    {
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());
        $oldName = $aggregate->getBudgetEnvelopeName();
        $aggregate->rename($command->getBudgetEnvelopeName(), $command->getBudgetEnvelopeUserId());
        $aggregatesToSave = BudgetEnvelopeNameRegistryBuilder::build(
            $this->eventSourcedRepository,
            $this->uuidGenerator,
        )
            ->loadOldRegistry(
                BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                    $command->getBudgetEnvelopeUserId(),
                    $oldName,
                    $this->uuidGenerator,
                ),
            )
            ->releaseName($oldName, $command->getBudgetEnvelopeUserId(), $command->getBudgetEnvelopeId())
            ->loadOrCreateRegistry(
                BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                    $command->getBudgetEnvelopeUserId(),
                    $command->getBudgetEnvelopeName(),
                    $this->uuidGenerator,
                ),
            )
            ->ensureNameIsAvailable(
                $command->getBudgetEnvelopeName(),
                $command->getBudgetEnvelopeUserId(),
                $command->getBudgetEnvelopeId(),
            )
            ->registerName(
                $command->getBudgetEnvelopeName(),
                $command->getBudgetEnvelopeUserId(),
                $command->getBudgetEnvelopeId(),
            )
            ->getRegistryAggregates();
        $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
    }
}
