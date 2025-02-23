<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanIncomeGeneratedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanIncomeEntryView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
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
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): void {
        foreach ($budgetPlanGeneratedDomainEvent->incomes as $income) {
            $this->budgetPlanIncomeEntryViewRepository->save(
                BudgetPlanIncomeEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $income,
                    $budgetPlanGeneratedDomainEvent->aggregateId,
                    $budgetPlanGeneratedDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [BudgetPlanIncomeGeneratedNotificationEvent::fromDomainEvent($budgetPlanGeneratedDomainEvent)],
                );
            } catch (\Exception $e) {
            }
        }
    }
}
