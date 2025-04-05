<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanWantAddedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanWantAdjustedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanWantRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanWantEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetPlanWantEntryProjection
{
    public function __construct(
        private BudgetPlanWantEntryViewRepositoryInterface $budgetPlanWantEntryViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->handleBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanWantAddedDomainEvent::class => $this->handleBudgetPlanWantAddedDomainEvent($event),
            BudgetPlanWantAdjustedDomainEvent::class => $this->handleBudgetPlanWantAdjustedDomainEvent($event),
            BudgetPlanWantRemovedDomainEvent::class => $this->handleBudgetPlanWantRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $event,
    ): void {
        foreach ($event->wants as $want) {
            $this->budgetPlanWantEntryViewRepository->save(
                BudgetPlanWantEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $want,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanWantAddedNotificationEvent::fromBudgetPlanGeneratedDomainEvent(
                            $event,
                        ),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
    ): void {
        foreach ($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->wants as $want) {
            $this->budgetPlanWantEntryViewRepository->save(
                BudgetPlanWantEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                    $want,
                    $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->aggregateId,
                    $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanWantAddedNotificationEvent::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                            $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
                        ),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanWantAddedDomainEvent(
        BudgetPlanWantAddedDomainEvent $budgetPlanWantAddedDomainEvent,
    ): void {
        $this->budgetPlanWantEntryViewRepository->save(
            BudgetPlanWantEntryView::fromBudgetPlanWantAddedDomainEvent($budgetPlanWantAddedDomainEvent),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanWantAddedNotificationEvent::fromBudgetPlanWantAddedDomainEvent(
                        $budgetPlanWantAddedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanWantAdjustedDomainEvent(
        BudgetPlanWantAdjustedDomainEvent $budgetPlanWantAdjustedDomainEvent,
    ): void {
        $budgetPlanWantView = $this->budgetPlanWantEntryViewRepository->findOneByUuid(
            $budgetPlanWantAdjustedDomainEvent->uuid,
        );

        if (!$budgetPlanWantView instanceof BudgetPlanWantEntryViewInterface) {
            return;
        }

        $budgetPlanWantView->fromEvent($budgetPlanWantAdjustedDomainEvent);
        $this->budgetPlanWantEntryViewRepository->save($budgetPlanWantView);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanWantAdjustedNotificationEvent::fromBudgetPlanWantAdjustedDomainEvent(
                        $budgetPlanWantAdjustedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanWantRemovedDomainEvent(
        BudgetPlanWantRemovedDomainEvent $budgetPlanWantRemovedDomainEvent,
    ): void {
        $this->budgetPlanWantEntryViewRepository->delete($budgetPlanWantRemovedDomainEvent->uuid);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanWantRemovedNotificationEvent::fromDomainEvent(
                        $budgetPlanWantRemovedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }
}
