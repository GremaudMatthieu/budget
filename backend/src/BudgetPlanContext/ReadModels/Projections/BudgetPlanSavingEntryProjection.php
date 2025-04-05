<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanSavingAddedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanSavingAdjustedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanSavingRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanSavingEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetPlanSavingEntryProjection
{
    public function __construct(
        private BudgetPlanSavingEntryViewRepositoryInterface $budgetPlanSavingEntryViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->handleBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanSavingAddedDomainEvent::class => $this->handleBudgetPlanSavingAddedDomainEvent($event),
            BudgetPlanSavingAdjustedDomainEvent::class => $this->handleBudgetPlanSavingAdjustedDomainEvent($event),
            BudgetPlanSavingRemovedDomainEvent::class => $this->handleBudgetPlanSavingRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        foreach ($event->savings as $saving) {
            $this->budgetPlanSavingEntryViewRepository->save(
                BudgetPlanSavingEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $saving,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanSavingAddedNotificationEvent::fromBudgetPlanGeneratedDomainEvent($event),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): void {
        foreach ($event->savings as $saving) {
            $this->budgetPlanSavingEntryViewRepository->save(
                BudgetPlanSavingEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                    $saving,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanSavingAddedNotificationEvent::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                            $event,
                        ),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanSavingAddedDomainEvent(BudgetPlanSavingAddedDomainEvent $event): void
    {
        $this->budgetPlanSavingEntryViewRepository->save(
            BudgetPlanSavingEntryView::fromBudgetPlanSavingAddedDomainEvent($event),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanSavingAddedNotificationEvent::fromBudgetPlanSavingAddedDomainEvent($event),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanSavingAdjustedDomainEvent(BudgetPlanSavingAdjustedDomainEvent $event): void
    {
        $budgetPlanSavingView = $this->budgetPlanSavingEntryViewRepository->findOneByUuid(
            $event->uuid,
        );

        if (!$budgetPlanSavingView instanceof BudgetPlanSavingEntryViewInterface) {
            return;
        }

        $budgetPlanSavingView->fromEvent($event);
        $this->budgetPlanSavingEntryViewRepository->save($budgetPlanSavingView);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanSavingAdjustedNotificationEvent::fromBudgetPlanSavingAdjustedDomainEvent($event),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanSavingRemovedDomainEvent(
        BudgetPlanSavingRemovedDomainEvent $event,
    ): void {
        $this->budgetPlanSavingEntryViewRepository->delete($event->uuid);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanSavingRemovedNotificationEvent::fromDomainEvent($event),
                ],
            );
        } catch (\Exception $e) {
        }
    }
}
