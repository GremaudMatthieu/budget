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

    private function handleBudgetEnvelopeCreditedDomainEvent(
        BudgetEnvelopeCreditedDomainEvent $budgetEnvelopeCreditedDomainEvent,
    ): void {
        $this->budgetEnvelopeLedgerEntryViewRepository->save(
            BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeCreditedDomainEvent(
                $budgetEnvelopeCreditedDomainEvent,
            ),
        );
        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeLedgerCreditEntryAddedNotificationEvent::fromDomainEvent($budgetEnvelopeCreditedDomainEvent),
            ]);
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeDebitedDomainEvent(
        BudgetEnvelopeDebitedDomainEvent $budgetEnvelopeDebitedDomainEvent,
    ): void {
        $this->budgetEnvelopeLedgerEntryViewRepository->save(
            BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeDebitedDomainEvent(
                $budgetEnvelopeDebitedDomainEvent,
            ),
        );
        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeLedgerDebitEntryAddedNotificationEvent::fromDomainEvent($budgetEnvelopeDebitedDomainEvent),
            ]);
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeRewoundDomainEvent(
        BudgetEnvelopeRewoundDomainEvent $budgetEnvelopeRewoundDomainEvent,
    ): void {
        $this->budgetEnvelopeLedgerEntryViewRepository->delete($budgetEnvelopeRewoundDomainEvent->aggregateId);
        $budgetEnvelopeEvents = $this->eventSourcedRepository->getByDomainEvents(
            $budgetEnvelopeRewoundDomainEvent->aggregateId,
            [BudgetEnvelopeCreditedDomainEvent::class, BudgetEnvelopeDebitedDomainEvent::class],
            UtcClock::fromStringToImmutable($budgetEnvelopeRewoundDomainEvent->desiredDateTime->format(\DateTimeInterface::ATOM)),
        );

        /** @var array{type: string, payload: string} $budgetEnvelopeEvent */
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
                BudgetEnvelopeLedgerEntriesRewoundNotificationEvent::fromDomainEvent($budgetEnvelopeRewoundDomainEvent),
            ]);
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeReplayedDomainEvent(
        BudgetEnvelopeReplayedDomainEvent $budgetEnvelopeReplayedDomainEvent,
    ): void {
        $this->budgetEnvelopeLedgerEntryViewRepository->delete($budgetEnvelopeReplayedDomainEvent->aggregateId);
        $budgetEnvelopeEvents = $this->eventSourcedRepository->getByDomainEvents(
            $budgetEnvelopeReplayedDomainEvent->aggregateId,
            [BudgetEnvelopeCreditedDomainEvent::class, BudgetEnvelopeDebitedDomainEvent::class],
            UtcClock::fromStringToImmutable(($budgetEnvelopeReplayedDomainEvent->updatedAt)->format(\DateTimeInterface::ATOM)),
        );

        /** @var array{type: string, payload: string} $budgetEnvelopeEvent */
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
                BudgetEnvelopeLedgerEntriesReplayedNotificationEvent::fromDomainEvent($budgetEnvelopeReplayedDomainEvent),
            ]);
        } catch (\Exception) {
        }
    }
}
