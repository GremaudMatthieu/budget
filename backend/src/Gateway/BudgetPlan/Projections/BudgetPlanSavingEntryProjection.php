<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewRepositoryInterface;
use App\Gateway\BudgetPlan\Views\BudgetPlanSavingEntryView;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetPlanSavingEntryProjection
{
    public function __construct(
        private BudgetPlanSavingEntryViewRepositoryInterface $budgetPlanSavingEntryViewRepository,
        private KeyManagementRepositoryInterface $keyManagementRepository,
        private EventEncryptorInterface $eventEncryptor,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        $encryptionKey = $this->keyManagementRepository->getKey($event->userId);

        if (!$encryptionKey) {
            return;
        }

        $event = $this->eventEncryptor->decrypt($event, $event->userId);
        match($event::class) {
            BudgetPlanGeneratedDomainEvent_v1::class => $this->handleBudgetPlanGeneratedDomainEvent_v1($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1($event),
            BudgetPlanSavingAddedDomainEvent_v1::class => $this->handleBudgetPlanSavingAddedDomainEvent_v1($event),
            BudgetPlanSavingAdjustedDomainEvent_v1::class => $this->handleBudgetPlanSavingAdjustedDomainEvent_v1($event),
            BudgetPlanSavingRemovedDomainEvent_v1::class => $this->handleBudgetPlanSavingRemovedDomainEvent_v1($event),
            BudgetPlanRemovedDomainEvent_v1::class => $this->handleBudgetPlanRemovedDomainEvent_v1($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent_v1(BudgetPlanGeneratedDomainEvent_v1 $event): void
    {
        foreach ($event->savings as $saving) {
            $this->budgetPlanSavingEntryViewRepository->save(
                BudgetPlanSavingEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
                    $saving,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1 $event,
    ): void {
        foreach ($event->savings as $saving) {
            $this->budgetPlanSavingEntryViewRepository->save(
                BudgetPlanSavingEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
                    $saving,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanSavingAddedDomainEvent_v1(BudgetPlanSavingAddedDomainEvent_v1 $event): void
    {
        $this->budgetPlanSavingEntryViewRepository->save(
            BudgetPlanSavingEntryView::fromBudgetPlanSavingAddedDomainEvent_v1($event),
        );
    }

    private function handleBudgetPlanSavingAdjustedDomainEvent_v1(BudgetPlanSavingAdjustedDomainEvent_v1 $event): void
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

    private function handleBudgetPlanSavingRemovedDomainEvent_v1(BudgetPlanSavingRemovedDomainEvent_v1 $event): void
    {
        $this->budgetPlanSavingEntryViewRepository->delete($event->uuid);
    }

    private function handleBudgetPlanRemovedDomainEvent_v1(BudgetPlanRemovedDomainEvent_v1 $event): void
    {
        $this->budgetPlanSavingEntryViewRepository->deleteByBudgetPlanId($event->aggregateId);
    }
}
