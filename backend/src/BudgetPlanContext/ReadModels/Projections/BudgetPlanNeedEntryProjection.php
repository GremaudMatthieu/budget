<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanNeedGeneratedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanNeedEntryView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
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
                    [BudgetPlanNeedGeneratedNotificationEvent::fromDomainEvent($budgetPlanGeneratedDomainEvent)],
                );
            } catch (\Exception $e) {
            }
        }
    }
}
