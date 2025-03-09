<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanNeedAddedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanNeedAdjustedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanNeedRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanNeedEntryView;
use App\Libraries\FluxCapacitor\Ports\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetPlanNeedEntryProjection
{
    public function __construct(
        private BudgetPlanNeedEntryViewRepositoryInterface $budgetPlanNeedEntryViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->handleBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanNeedAddedDomainEvent::class => $this->handleBudgetPlanNeedAddedDomainEvent($event),
            BudgetPlanNeedAdjustedDomainEvent::class => $this->handleBudgetPlanNeedAdjustedDomainEvent($event),
            BudgetPlanNeedRemovedDomainEvent::class => $this->handleBudgetPlanNeedRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): void {
        foreach ($budgetPlanGeneratedDomainEvent->needs as $need) {
            $this->budgetPlanNeedEntryViewRepository->save(
                BudgetPlanNeedEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $need,
                    $budgetPlanGeneratedDomainEvent->aggregateId,
                    $budgetPlanGeneratedDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanNeedAddedNotificationEvent::fromBudgetPlanGeneratedDomainEvent(
                            $budgetPlanGeneratedDomainEvent,
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
        foreach ($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->needs as $need) {
            $this->budgetPlanNeedEntryViewRepository->save(
                BudgetPlanNeedEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                    $need,
                    $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->aggregateId,
                    $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanNeedAddedNotificationEvent::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                            $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
                        ),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanNeedAddedDomainEvent(
        BudgetPlanNeedAddedDomainEvent $budgetPlanNeedAddedDomainEvent,
    ): void {
        $this->budgetPlanNeedEntryViewRepository->save(
            BudgetPlanNeedEntryView::fromBudgetPlanNeedAddedDomainEvent($budgetPlanNeedAddedDomainEvent),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanNeedAddedNotificationEvent::fromBudgetPlanNeedAddedDomainEvent(
                        $budgetPlanNeedAddedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanNeedAdjustedDomainEvent(
        BudgetPlanNeedAdjustedDomainEvent $budgetPlanNeedAdjustedDomainEvent,
    ): void {
        $budgetPlanNeedView = $this->budgetPlanNeedEntryViewRepository->findOneByUuid(
            $budgetPlanNeedAdjustedDomainEvent->uuid,
        );

        if (!$budgetPlanNeedView instanceof BudgetPlanNeedEntryViewInterface) {
            return;
        }

        $budgetPlanNeedView->fromEvent($budgetPlanNeedAdjustedDomainEvent);
        $this->budgetPlanNeedEntryViewRepository->save($budgetPlanNeedView);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanNeedAdjustedNotificationEvent::fromBudgetPlanNeedAdjustedDomainEvent(
                        $budgetPlanNeedAdjustedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanNeedRemovedDomainEvent(
        BudgetPlanNeedRemovedDomainEvent $budgetPlanNeedRemovedDomainEvent,
    ): void {
        $this->budgetPlanNeedEntryViewRepository->delete($budgetPlanNeedRemovedDomainEvent->uuid);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanNeedRemovedNotificationEvent::fromDomainEvent(
                        $budgetPlanNeedRemovedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }
}
