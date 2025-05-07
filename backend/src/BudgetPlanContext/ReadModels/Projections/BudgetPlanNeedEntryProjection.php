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
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanNeedEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetPlanNeedEntryProjection
{
    public function __construct(private BudgetPlanNeedEntryViewRepositoryInterface $budgetPlanNeedEntryViewRepository)
    {
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

    private function handleBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        foreach ($event->needs as $need) {
            $this->budgetPlanNeedEntryViewRepository->save(
                BudgetPlanNeedEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $need,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): void {
        foreach ($event->needs as $need) {
            $this->budgetPlanNeedEntryViewRepository->save(
                BudgetPlanNeedEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                    $need,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanNeedAddedDomainEvent(BudgetPlanNeedAddedDomainEvent $event): void
    {
        $this->budgetPlanNeedEntryViewRepository->save(
            BudgetPlanNeedEntryView::fromBudgetPlanNeedAddedDomainEvent($event),
        );
    }

    private function handleBudgetPlanNeedAdjustedDomainEvent(BudgetPlanNeedAdjustedDomainEvent $event): void
    {
        $budgetPlanNeedView = $this->budgetPlanNeedEntryViewRepository->findOneByUuid($event->uuid);

        if (!$budgetPlanNeedView instanceof BudgetPlanNeedEntryViewInterface) {
            return;
        }

        $budgetPlanNeedView->fromEvent($event);
        $this->budgetPlanNeedEntryViewRepository->save($budgetPlanNeedView);
    }

    private function handleBudgetPlanNeedRemovedDomainEvent(BudgetPlanNeedRemovedDomainEvent $event): void
    {
        $this->budgetPlanNeedEntryViewRepository->delete($event->uuid);
    }
}
