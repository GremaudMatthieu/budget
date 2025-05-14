<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanWantEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetPlanWantEntryProjection
{
    public function __construct(
        private BudgetPlanWantEntryViewRepositoryInterface $budgetPlanWantEntryViewRepository,
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
            BudgetPlanRemovedDomainEvent::class => $this->handleBudgetPlanRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        foreach ($event->wants as $want) {
            $this->budgetPlanWantEntryViewRepository->save(
                BudgetPlanWantEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $want,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): void {
        foreach ($event->wants as $want) {
            $this->budgetPlanWantEntryViewRepository->save(
                BudgetPlanWantEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                    $want,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanWantAddedDomainEvent(BudgetPlanWantAddedDomainEvent $event): void
    {
        $this->budgetPlanWantEntryViewRepository->save(
            BudgetPlanWantEntryView::fromBudgetPlanWantAddedDomainEvent($event),
        );
    }

    private function handleBudgetPlanWantAdjustedDomainEvent(BudgetPlanWantAdjustedDomainEvent $event): void
    {
        $budgetPlanWantView = $this->budgetPlanWantEntryViewRepository->findOneByUuid($event->uuid);

        if (!$budgetPlanWantView instanceof BudgetPlanWantEntryViewInterface) {
            return;
        }

        $budgetPlanWantView->fromEvent($event);
        $this->budgetPlanWantEntryViewRepository->save($budgetPlanWantView);
    }

    private function handleBudgetPlanWantRemovedDomainEvent(BudgetPlanWantRemovedDomainEvent $event): void
    {
        $this->budgetPlanWantEntryViewRepository->delete($event->uuid);
    }

    private function handleBudgetPlanRemovedDomainEvent(BudgetPlanRemovedDomainEvent $event): void
    {
        $this->budgetPlanWantEntryViewRepository->deleteByBudgetPlanId($event->aggregateId);
    }
}
