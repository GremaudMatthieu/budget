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
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetEnvelopeProjection
{
    public function __construct(private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository)
    {
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
    }
}
