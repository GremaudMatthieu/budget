<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\RenameABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RenameABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    public function __invoke(RenameABudgetEnvelopeCommand $renameABudgetEnvelopeCommand): void
    {
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $renameABudgetEnvelopeCommand->getBudgetEnvelopeId(),
            ),
        );
        $aggregate->rename(
            $renameABudgetEnvelopeCommand->getBudgetEnvelopeName(),
            $renameABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
            $renameABudgetEnvelopeCommand->getBudgetEnvelopeId(),
            $this->budgetEnvelopeViewRepository,
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}