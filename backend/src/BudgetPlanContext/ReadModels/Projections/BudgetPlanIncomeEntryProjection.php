<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanIncomeEntryView;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetPlanIncomeEntryProjection
{
    public function __construct(
        private BudgetPlanIncomeEntryViewRepositoryInterface $budgetPlanIncomeEntryViewRepository,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent_v1::class => $this->handleBudgetPlanGeneratedDomainEvent_v1($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1($event),
            BudgetPlanIncomeAddedDomainEvent_v1::class => $this->handleBudgetPlanIncomeAddedDomainEvent_v1($event),
            BudgetPlanIncomeAdjustedDomainEvent_v1::class => $this->handleBudgetPlanIncomeAdjustedDomainEvent_v1($event),
            BudgetPlanIncomeRemovedDomainEvent_v1::class => $this->handleBudgetPlanIncomeRemovedDomainEvent_v1($event),
            BudgetPlanRemovedDomainEvent_v1::class => $this->handleBudgetPlanRemovedDomainEvent_v1($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent_v1(BudgetPlanGeneratedDomainEvent_v1 $event): void
    {
        foreach ($event->incomes as $income) {
            $this->budgetPlanIncomeEntryViewRepository->save(
                BudgetPlanIncomeEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
                    $income,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1 $event,
    ): void {
        foreach ($event->incomes as $income) {
            $this->budgetPlanIncomeEntryViewRepository->save(
                BudgetPlanIncomeEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
                    $income,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanIncomeAddedDomainEvent_v1(BudgetPlanIncomeAddedDomainEvent_v1 $event): void
    {
        $this->budgetPlanIncomeEntryViewRepository->save(
            BudgetPlanIncomeEntryView::fromBudgetPlanIncomeAddedDomainEvent_v1($event),
        );
    }

    private function handleBudgetPlanIncomeAdjustedDomainEvent_v1(BudgetPlanIncomeAdjustedDomainEvent_v1 $event): void
    {
        $budgetPlanIncomeView = $this->budgetPlanIncomeEntryViewRepository->findOneByUuid(
            $event->uuid,
        );

        if (!$budgetPlanIncomeView instanceof BudgetPlanIncomeEntryViewInterface) {
            return;
        }

        $budgetPlanIncomeView->fromEvent($event);
        $this->budgetPlanIncomeEntryViewRepository->save($budgetPlanIncomeView);
    }

    private function handleBudgetPlanIncomeRemovedDomainEvent_v1(BudgetPlanIncomeRemovedDomainEvent_v1 $event): void
    {
        $this->budgetPlanIncomeEntryViewRepository->delete($event->uuid);
    }

    private function handleBudgetPlanRemovedDomainEvent_v1(BudgetPlanRemovedDomainEvent_v1 $event): void
    {
        $this->budgetPlanIncomeEntryViewRepository->deleteByBudgetPlanId($event->aggregateId);
    }
}
