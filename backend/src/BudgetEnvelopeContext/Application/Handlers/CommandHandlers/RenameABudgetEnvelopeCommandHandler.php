<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\RenameABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Builders\BudgetEnvelopeNameRegistryBuilder;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeNameRegistryId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;

final readonly class RenameABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function __invoke(RenameABudgetEnvelopeCommand $command): void
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
