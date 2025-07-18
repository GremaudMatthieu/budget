<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewRepositoryInterface;
use App\Gateway\BudgetEnvelope\Views\BudgetEnvelopeLedgerEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\ValueObjects\UtcClock;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetEnvelopeLedgerEntryProjection
{
    public function __construct(
        private BudgetEnvelopeLedgerEntryViewRepositoryInterface $budgetEnvelopeLedgerEntryViewRepository,
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetEnvelopeCreditedDomainEvent_v1::class => $this->handleBudgetEnvelopeCreditedDomainEvent_v1($event),
            BudgetEnvelopeDebitedDomainEvent_v1::class => $this->handleBudgetEnvelopeDebitedDomainEvent_v1($event),
            BudgetEnvelopeRewoundDomainEvent_v1::class => $this->handleBudgetEnvelopeRewoundDomainEvent_v1($event),
            BudgetEnvelopeReplayedDomainEvent_v1::class => $this->handleBudgetEnvelopeReplayedDomainEvent_v1($event),
            default => null,
        };
    }

    private function handleBudgetEnvelopeCreditedDomainEvent_v1(BudgetEnvelopeCreditedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository->save(
            BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeCreditedDomainEvent_v1($event),
        );
    }

    private function handleBudgetEnvelopeDebitedDomainEvent_v1(BudgetEnvelopeDebitedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository->save(
            BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeDebitedDomainEvent_v1($event),
        );
    }

    private function handleBudgetEnvelopeRewoundDomainEvent_v1(BudgetEnvelopeRewoundDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository->delete($event->aggregateId);
        $budgetEnvelopeEvents = $this->eventSourcedRepository->getByDomainEvents(
            $event->aggregateId,
            [BudgetEnvelopeCreditedDomainEvent_v1::class, BudgetEnvelopeDebitedDomainEvent_v1::class],
            UtcClock::fromStringToImmutable($event->desiredDateTime->format(\DateTimeInterface::ATOM)),
        );

        /** @var array{event_name: string, payload: string} $budgetEnvelopeEvent */
        foreach ($budgetEnvelopeEvents as $budgetEnvelopeEvent) {
            match ($this->eventClassMap->getEventPathByClassName($budgetEnvelopeEvent['event_name'])) {
                BudgetEnvelopeCreditedDomainEvent_v1::class => $this->handleBudgetEnvelopeCreditedDomainEvent_v1(
                    BudgetEnvelopeCreditedDomainEvent_v1::fromArray(
                        json_decode($budgetEnvelopeEvent['payload'], true),
                    ),
                ),
                BudgetEnvelopeDebitedDomainEvent_v1::class => $this->handleBudgetEnvelopeDebitedDomainEvent_v1(
                    BudgetEnvelopeDebitedDomainEvent_v1::fromArray(
                        json_decode($budgetEnvelopeEvent['payload'], true),
                    ),
                ),
                default => null,
            };
        }
    }

    private function handleBudgetEnvelopeReplayedDomainEvent_v1(BudgetEnvelopeReplayedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository->delete($event->aggregateId);
        $budgetEnvelopeEvents = $this->eventSourcedRepository->getByDomainEvents(
            $event->aggregateId,
            [BudgetEnvelopeCreditedDomainEvent_v1::class, BudgetEnvelopeDebitedDomainEvent_v1::class],
            UtcClock::fromStringToImmutable($event->updatedAt->format(\DateTimeInterface::ATOM)),
        );

        /** @var array{event_name: string, payload: string} $budgetEnvelopeEvent */
        foreach ($budgetEnvelopeEvents as $budgetEnvelopeEvent) {
            match ($this->eventClassMap->getEventPathByClassName($budgetEnvelopeEvent['event_name'])) {
                BudgetEnvelopeCreditedDomainEvent_v1::class => $this->handleBudgetEnvelopeCreditedDomainEvent_v1(
                    BudgetEnvelopeCreditedDomainEvent_v1::fromArray(
                        json_decode($budgetEnvelopeEvent['payload'], true),
                    ),
                ),
                BudgetEnvelopeDebitedDomainEvent_v1::class => $this->handleBudgetEnvelopeDebitedDomainEvent_v1(
                    BudgetEnvelopeDebitedDomainEvent_v1::fromArray(
                        json_decode($budgetEnvelopeEvent['payload'], true),
                    ),
                ),
                default => null,
            };
        }
    }
}
