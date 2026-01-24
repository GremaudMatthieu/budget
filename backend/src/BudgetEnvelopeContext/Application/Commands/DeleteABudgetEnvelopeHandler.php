<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeNameRegistryBuilder;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeNameRegistryId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;

final readonly class DeleteABudgetEnvelopeHandler
{
    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    public function __invoke(DeleteABudgetEnvelope $command): void
    {
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());
        $aggregate->delete($command->getBudgetEnvelopeUserId());
        BudgetEnvelopeNameRegistryBuilder::build(
            $this->eventSourcedRepository,
            $this->uuidGenerator,
        )
            ->loadOldRegistry(
                BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                    $command->getBudgetEnvelopeUserId(),
                    $aggregate->getBudgetEnvelopeName(),
                    $this->uuidGenerator,
                ),
            )
            ->releaseName(
                $aggregate->getBudgetEnvelopeName(),
                $command->getBudgetEnvelopeUserId(),
                $command->getBudgetEnvelopeId(),
            );
    }
}
