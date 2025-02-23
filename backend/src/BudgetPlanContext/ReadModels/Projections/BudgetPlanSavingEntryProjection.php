<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanSavingGeneratedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanSavingEntryView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
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
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): void {
        foreach ($budgetPlanGeneratedDomainEvent->savings as $saving) {
            $budgetPlanSavingEntryView = BudgetPlanSavingEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                $saving,
                $budgetPlanGeneratedDomainEvent->aggregateId,
                $budgetPlanGeneratedDomainEvent->occurredOn,
            );
            $this->budgetPlanSavingEntryViewRepository->save($budgetPlanSavingEntryView);
            try {
                $this->publisher->publishNotificationEvents(
                    [BudgetPlanSavingGeneratedNotificationEvent::fromDomainEvent($budgetPlanGeneratedDomainEvent)],
                );
            } catch (\Exception $e) {
            }
        }
    }
}
