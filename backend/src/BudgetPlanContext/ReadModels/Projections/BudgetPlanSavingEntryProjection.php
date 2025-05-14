<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanSavingEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetPlanSavingEntryProjection
{
    public function __construct(
        private BudgetPlanSavingEntryViewRepositoryInterface $budgetPlanSavingEntryViewRepository,
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
            BudgetPlanRemovedDomainEvent::class => $this->handleBudgetPlanRemovedDomainEvent($event),
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
        }
    }

    private function handleBudgetPlanSavingAddedDomainEvent(BudgetPlanSavingAddedDomainEvent $event): void
    {
        $this->budgetPlanSavingEntryViewRepository->save(
            BudgetPlanSavingEntryView::fromBudgetPlanSavingAddedDomainEvent($event),
        );
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
    }

    private function handleBudgetPlanSavingRemovedDomainEvent(BudgetPlanSavingRemovedDomainEvent $event): void
    {
        $this->budgetPlanSavingEntryViewRepository->delete($event->uuid);
    }

    private function handleBudgetPlanRemovedDomainEvent(BudgetPlanRemovedDomainEvent $event): void
    {
        $this->budgetPlanSavingEntryViewRepository->deleteByBudgetPlanId($event->aggregateId);
    }
}
