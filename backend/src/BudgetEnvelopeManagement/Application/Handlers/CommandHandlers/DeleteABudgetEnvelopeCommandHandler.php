<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\DeleteABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class DeleteABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(DeleteABudgetEnvelopeCommand $deleteABudgetEnvelopeCommand): void
    {
        $events = $this->eventSourcedRepository->get($deleteABudgetEnvelopeCommand->getUuid());
        $aggregate = BudgetEnvelope::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->delete(BudgetEnvelopeUserId::create($deleteABudgetEnvelopeCommand->getUserUuid()));
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}