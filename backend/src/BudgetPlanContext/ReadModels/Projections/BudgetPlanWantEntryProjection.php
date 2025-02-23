<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanWantGeneratedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanWantEntryView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
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
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): void {
        foreach ($budgetPlanGeneratedDomainEvent->wants as $want) {
            $this->budgetPlanWantEntryViewRepository->save(
                BudgetPlanWantEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $want,
                    $budgetPlanGeneratedDomainEvent->aggregateId,
                    $budgetPlanGeneratedDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [BudgetPlanWantGeneratedNotificationEvent::fromDomainEvent($budgetPlanGeneratedDomainEvent)],
                );
            } catch (\Exception $e) {
            }
        }
    }
}
