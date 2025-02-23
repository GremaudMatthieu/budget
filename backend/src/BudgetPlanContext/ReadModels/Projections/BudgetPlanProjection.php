<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanGeneratedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
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
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): void {
        $this->budgetPlanViewRepository->save(
            BudgetPlanView::fromBudgetPlanGeneratedDomainEvent($budgetPlanGeneratedDomainEvent),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetPlanGeneratedNotificationEvent::fromDomainEvent($budgetPlanGeneratedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
    }
}
