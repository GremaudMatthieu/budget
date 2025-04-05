<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewRepositoryInterface;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeLedgerCreditEntryAddedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeLedgerDebitEntryAddedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeLedgerEntriesReplayedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeLedgerEntriesRewoundNotificationEvent;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeLedgerEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final readonly class BudgetEnvelopeLedgerEntryProjection
{
    public function __construct(
        private BudgetEnvelopeLedgerEntryViewRepositoryInterface $budgetEnvelopeLedgerEntryViewRepository,
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private PublisherInterface $publisher,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent($event),
            BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent($event),
            BudgetEnvelopeRewoundDomainEvent::class => $this->handleBudgetEnvelopeRewoundDomainEvent($event),
            BudgetEnvelopeReplayedDomainEvent::class => $this->handleBudgetEnvelopeReplayedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetEnvelopeCreditedDomainEvent(BudgetEnvelopeCreditedDomainEvent $event): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository->save(
            BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeCreditedDomainEvent($event),
        );
        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeLedgerCreditEntryAddedNotificationEvent::fromDomainEvent($event),
            ]);
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeDebitedDomainEvent(BudgetEnvelopeDebitedDomainEvent $event): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository->save(
            BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeDebitedDomainEvent($event),
        );
        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeLedgerDebitEntryAddedNotificationEvent::fromDomainEvent($event),
            ]);
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeRewoundDomainEvent(BudgetEnvelopeRewoundDomainEvent $event): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository->delete($event->aggregateId);
        $budgetEnvelopeEvents = $this->eventSourcedRepository->getByDomainEvents(
            $event->aggregateId,
            [BudgetEnvelopeCreditedDomainEvent::class, BudgetEnvelopeDebitedDomainEvent::class],
            UtcClock::fromStringToImmutable($event->desiredDateTime->format(\DateTimeInterface::ATOM)),
        );

        /** @var array{event_name: string, payload: string} $budgetEnvelopeEvent */
        foreach ($budgetEnvelopeEvents as $budgetEnvelopeEvent) {
            match ($this->eventClassMap->getEventPathByClassName($budgetEnvelopeEvent['event_name'])) {
                BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent(
                    BudgetEnvelopeCreditedDomainEvent::fromArray(
                        (json_decode($budgetEnvelopeEvent['payload'], true)),
                    ),
                ),
                BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent(
                    BudgetEnvelopeDebitedDomainEvent::fromArray(
                        (json_decode($budgetEnvelopeEvent['payload'], true)),
                    ),
                ),
                default => null,
            };
        }

        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeLedgerEntriesRewoundNotificationEvent::fromDomainEvent($event),
            ]);
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeReplayedDomainEvent(BudgetEnvelopeReplayedDomainEvent $event): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository->delete($event->aggregateId);
        $budgetEnvelopeEvents = $this->eventSourcedRepository->getByDomainEvents(
            $event->aggregateId,
            [BudgetEnvelopeCreditedDomainEvent::class, BudgetEnvelopeDebitedDomainEvent::class],
            UtcClock::fromStringToImmutable(($event->updatedAt)->format(\DateTimeInterface::ATOM)),
        );

        /** @var array{event_name: string, payload: string} $budgetEnvelopeEvent */
        foreach ($budgetEnvelopeEvents as $budgetEnvelopeEvent) {
            match ($this->eventClassMap->getEventPathByClassName($budgetEnvelopeEvent['event_name'])) {
                BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent(
                    BudgetEnvelopeCreditedDomainEvent::fromArray(
                        json_decode($budgetEnvelopeEvent['payload'], true),
                    ),
                ),
                BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent(
                    BudgetEnvelopeDebitedDomainEvent::fromArray(
                        json_decode($budgetEnvelopeEvent['payload'], true),
                    ),
                ),
                default => null,
            };
        }

        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeLedgerEntriesReplayedNotificationEvent::fromDomainEvent($event),
            ]);
        } catch (\Exception) {
        }
    }
}
