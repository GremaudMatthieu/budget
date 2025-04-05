<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanCurrencyChangedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanCurrencyChangedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanGeneratedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanGeneratedWithOneThatAlreadyExistsNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetPlanProjection
{
    public function __construct(
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->handleBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanRemovedDomainEvent::class => $this->handleBudgetPlanRemovedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanCurrencyChangedDomainEvent::class => $this->handleBudgetPlanCurrencyChangedDomainEvent($event),
            BudgetPlanIncomeAddedDomainEvent::class => $this->handleBudgetPlanIncomeAddedDomainEvent($event),
            BudgetPlanSavingAddedDomainEvent::class => $this->handleBudgetPlanSavingAddedDomainEvent($event),
            BudgetPlanNeedAddedDomainEvent::class => $this->handleBudgetPlanNeedAddedDomainEvent($event),
            BudgetPlanWantAddedDomainEvent::class => $this->handleBudgetPlanWantAddedDomainEvent($event),
            BudgetPlanIncomeAdjustedDomainEvent::class => $this->handleBudgetPlanIncomeAdjustedDomainEvent($event),
            BudgetPlanNeedAdjustedDomainEvent::class => $this->handleBudgetPlanNeedAdjustedDomainEvent($event),
            BudgetPlanSavingAdjustedDomainEvent::class => $this->handleBudgetPlanSavingAdjustedDomainEvent($event),
            BudgetPlanWantAdjustedDomainEvent::class => $this->handleBudgetPlanWantAdjustedDomainEvent($event),
            BudgetPlanIncomeRemovedDomainEvent::class => $this->handleBudgetPlanIncomeRemovedDomainEvent($event),
            BudgetPlanWantRemovedDomainEvent::class => $this->handleBudgetPlanWantRemovedDomainEvent($event),
            BudgetPlanNeedRemovedDomainEvent::class => $this->handleBudgetPlanNeedRemovedDomainEvent($event),
            BudgetPlanSavingRemovedDomainEvent::class => $this->handleBudgetPlanSavingRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        $this->budgetPlanViewRepository->save(BudgetPlanView::fromBudgetPlanGeneratedDomainEvent($event));
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanGeneratedNotificationEvent::fromDomainEvent($event),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): void {
        $this->budgetPlanViewRepository->save(
            BudgetPlanView::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanGeneratedWithOneThatAlreadyExistsNotificationEvent::fromDomainEvent($event),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanRemovedDomainEvent(BudgetPlanRemovedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
        try {
            $this->publisher->publishNotificationEvents([BudgetPlanRemovedNotificationEvent::fromDomainEvent($event)]);
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanCurrencyChangedDomainEvent(BudgetPlanCurrencyChangedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetPlanCurrencyChangedNotificationEvent::fromDomainEvent($event)],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanIncomeAddedDomainEvent(BudgetPlanIncomeAddedDomainEvent $event): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingAddedDomainEvent(BudgetPlanSavingAddedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedAddedDomainEvent(BudgetPlanNeedAddedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantAddedDomainEvent(BudgetPlanWantAddedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanIncomeAdjustedDomainEvent(BudgetPlanIncomeAdjustedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedAdjustedDomainEvent(BudgetPlanNeedAdjustedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingAdjustedDomainEvent(BudgetPlanSavingAdjustedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantAdjustedDomainEvent(BudgetPlanWantAdjustedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanIncomeRemovedDomainEvent(BudgetPlanIncomeRemovedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantRemovedDomainEvent(BudgetPlanWantRemovedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedRemovedDomainEvent(BudgetPlanNeedRemovedDomainEvent $event): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingRemovedDomainEvent(BudgetPlanSavingRemovedDomainEvent $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }
}
