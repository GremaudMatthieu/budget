<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeAddedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeCreditedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeCurrencyChangedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeDebitedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeDeletedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeRenamedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeReplayedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeRewoundNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeTargetedAmountChangedNotificationEvent;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetEnvelopeProjection
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetEnvelopeAddedDomainEvent::class => $this->handleBudgetEnvelopeAddedDomainEvent($event),
            BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent($event),
            BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent($event),
            BudgetEnvelopeRenamedDomainEvent::class => $this->handleBudgetEnvelopeNamedDomainEvent($event),
            BudgetEnvelopeDeletedDomainEvent::class => $this->handleBudgetEnvelopeDeletedDomainEvent($event),
            BudgetEnvelopeRewoundDomainEvent::class => $this->handleBudgetEnvelopeRewoundDomainEvent($event),
            BudgetEnvelopeReplayedDomainEvent::class => $this->handleBudgetEnvelopeReplayedDomainEvent($event),
            BudgetEnvelopeTargetedAmountChangedDomainEvent::class => $this->handleBudgetEnvelopeTargetedAmountChangedDomainEvent($event),
            BudgetEnvelopeCurrencyChangedDomainEvent::class => $this->handleBudgetEnvelopeCurrencyChangedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetEnvelopeAddedDomainEvent(BudgetEnvelopeAddedDomainEvent $event): void
    {
        $this->budgetEnvelopeViewRepository->save(BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent($event));
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeAddedNotificationEvent::fromDomainEvent($event)],
            );
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeCreditedDomainEvent(BudgetEnvelopeCreditedDomainEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeCreditedNotificationEvent::fromDomainEvent($event)],
            );
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeDebitedDomainEvent(BudgetEnvelopeDebitedDomainEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeDebitedNotificationEvent::fromDomainEvent($event)],
            );
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeNamedDomainEvent(BudgetEnvelopeRenamedDomainEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeRenamedNotificationEvent::fromDomainEvent($event)],
            );
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeDeletedDomainEvent(BudgetEnvelopeDeletedDomainEvent $event): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeDeletedNotificationEvent::fromDomainEvent($event)],
            );
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeRewoundDomainEvent(BudgetEnvelopeRewoundDomainEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeRewoundNotificationEvent::fromDomainEvent($event)],
            );
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeReplayedDomainEvent(BudgetEnvelopeReplayedDomainEvent $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeReplayedNotificationEvent::fromDomainEvent($event)],
            );
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeTargetedAmountChangedDomainEvent(
        BudgetEnvelopeTargetedAmountChangedDomainEvent $event,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeTargetedAmountChangedNotificationEvent::fromDomainEvent($event),
            ]);
        } catch (\Exception) {
        }
    }

    private function handleBudgetEnvelopeCurrencyChangedDomainEvent(
        BudgetEnvelopeCurrencyChangedDomainEvent $event,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeCurrencyChangedNotificationEvent::fromDomainEvent($event),
            ]);
        } catch (\Exception) {
        }
    }
}
