<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent_v1;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptor;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepository;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEvent;
use App\Presentation\BudgetPlan\BudgetPlanWantEntry;
use App\Presentation\BudgetPlan\BudgetPlanWantEntryFinder;
use App\Presentation\BudgetPlan\Views\BudgetPlanWantEntryView;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetPlanWantEntryProjection
{
    public function __construct(
        private BudgetPlanWantEntryFinder $budgetPlanWantEntryViewRepository,
        private KeyManagementRepository $keyManagementRepository,
        private EventEncryptor $eventEncryptor,
    ) {
    }

    public function __invoke(DomainEvent $event): void
    {
        $encryptionKey = $this->keyManagementRepository->getKey($event->userId);

        if (!$encryptionKey) {
            return;
        }

        $event = $this->eventEncryptor->decrypt($event, $event->userId);

        match($event::class) {
            BudgetPlanGeneratedDomainEvent_v1::class => $this->handleBudgetPlanGeneratedDomainEvent_v1($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1($event),
            BudgetPlanWantAddedDomainEvent_v1::class => $this->handleBudgetPlanWantAddedDomainEvent_v1($event),
            BudgetPlanWantAdjustedDomainEvent_v1::class => $this->handleBudgetPlanWantAdjustedDomainEvent_v1($event),
            BudgetPlanWantRemovedDomainEvent_v1::class => $this->handleBudgetPlanWantRemovedDomainEvent_v1($event),
            BudgetPlanRemovedDomainEvent_v1::class => $this->handleBudgetPlanRemovedDomainEvent_v1($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent_v1(BudgetPlanGeneratedDomainEvent_v1 $event): void
    {
        foreach ($event->wants as $want) {
            $this->budgetPlanWantEntryViewRepository->save(
                BudgetPlanWantEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
                    $want,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1 $event,
    ): void {
        foreach ($event->wants as $want) {
            $this->budgetPlanWantEntryViewRepository->save(
                BudgetPlanWantEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
                    $want,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanWantAddedDomainEvent_v1(BudgetPlanWantAddedDomainEvent_v1 $event): void
    {
        $this->budgetPlanWantEntryViewRepository->save(
            BudgetPlanWantEntryView::fromBudgetPlanWantAddedDomainEvent_v1($event),
        );
    }

    private function handleBudgetPlanWantAdjustedDomainEvent_v1(BudgetPlanWantAdjustedDomainEvent_v1 $event): void
    {
        $budgetPlanWantView = $this->budgetPlanWantEntryViewRepository->findOneByUuid($event->uuid);

        if (!$budgetPlanWantView instanceof BudgetPlanWantEntry) {
            return;
        }

        $budgetPlanWantView->fromEvent($event);
        $this->budgetPlanWantEntryViewRepository->save($budgetPlanWantView);
    }

    private function handleBudgetPlanWantRemovedDomainEvent_v1(BudgetPlanWantRemovedDomainEvent_v1 $event): void
    {
        $this->budgetPlanWantEntryViewRepository->delete($event->uuid);
    }

    private function handleBudgetPlanRemovedDomainEvent_v1(BudgetPlanRemovedDomainEvent_v1 $event): void
    {
        $this->budgetPlanWantEntryViewRepository->deleteByBudgetPlanId($event->aggregateId);
    }
}
