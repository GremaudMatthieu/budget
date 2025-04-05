<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanIncomeAddedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanIncomeAdjustedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanIncomeRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanIncomeEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetPlanIncomeEntryProjection
{
    public function __construct(
        private BudgetPlanIncomeEntryViewRepositoryInterface $budgetPlanIncomeEntryViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->handleBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanIncomeAddedDomainEvent::class => $this->handleBudgetPlanIncomeAddedDomainEvent($event),
            BudgetPlanIncomeAdjustedDomainEvent::class => $this->handleBudgetPlanIncomeAdjustedDomainEvent($event),
            BudgetPlanIncomeRemovedDomainEvent::class => $this->handleBudgetPlanIncomeRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void {
        foreach ($event->incomes as $income) {
            $this->budgetPlanIncomeEntryViewRepository->save(
                BudgetPlanIncomeEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $income,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanIncomeAddedNotificationEvent::fromBudgetPlanGeneratedDomainEvent($event),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): void {
        foreach ($event->incomes as $income) {
            $this->budgetPlanIncomeEntryViewRepository->save(
                BudgetPlanIncomeEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                    $income,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanIncomeAddedNotificationEvent::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                            $event,
                        ),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanIncomeAddedDomainEvent(BudgetPlanIncomeAddedDomainEvent $event): void
    {
        $this->budgetPlanIncomeEntryViewRepository->save(
            BudgetPlanIncomeEntryView::fromBudgetPlanIncomeAddedDomainEvent($event),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanIncomeAddedNotificationEvent::fromBudgetPlanIncomeAddedDomainEvent($event),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanIncomeAdjustedDomainEvent(BudgetPlanIncomeAdjustedDomainEvent $event): void
    {
        $budgetPlanIncomeView = $this->budgetPlanIncomeEntryViewRepository->findOneByUuid(
            $event->uuid,
        );

        if (!$budgetPlanIncomeView instanceof BudgetPlanIncomeEntryViewInterface) {
            return;
        }

        $budgetPlanIncomeView->fromEvent($event);
        $this->budgetPlanIncomeEntryViewRepository->save($budgetPlanIncomeView);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanIncomeAdjustedNotificationEvent::fromBudgetPlanIncomeAdjustedDomainEvent($event),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanIncomeRemovedDomainEvent(BudgetPlanIncomeRemovedDomainEvent $event): void
    {
        $this->budgetPlanIncomeEntryViewRepository->delete($event->uuid);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanIncomeRemovedNotificationEvent::fromDomainEvent($event),
                ],
            );
        } catch (\Exception $e) {
        }
    }
}
