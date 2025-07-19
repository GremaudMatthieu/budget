<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewRepositoryInterface;
use App\Gateway\BudgetPlan\Views\BudgetPlanNeedEntryView;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetPlanNeedEntryProjection
{
    public function __construct(
        private BudgetPlanNeedEntryViewRepositoryInterface $budgetPlanNeedEntryViewRepository,
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
            BudgetPlanNeedAddedDomainEvent_v1::class => $this->handleBudgetPlanNeedAddedDomainEvent_v1($event),
            BudgetPlanNeedAdjustedDomainEvent_v1::class => $this->handleBudgetPlanNeedAdjustedDomainEvent_v1($event),
            BudgetPlanNeedRemovedDomainEvent_v1::class => $this->handleBudgetPlanNeedRemovedDomainEvent_v1($event),
            BudgetPlanRemovedDomainEvent_v1::class => $this->handleBudgetPlanRemovedDomainEvent_v1($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent_v1(BudgetPlanGeneratedDomainEvent_v1 $event): void
    {
        foreach ($event->needs as $need) {
            $this->budgetPlanNeedEntryViewRepository->save(
                BudgetPlanNeedEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
                    $need,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1 $event,
    ): void {
        foreach ($event->needs as $need) {
            $this->budgetPlanNeedEntryViewRepository->save(
                BudgetPlanNeedEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
                    $need,
                    $event->aggregateId,
                    $event->occurredOn,
                ),
            );
        }
    }

    private function handleBudgetPlanNeedAddedDomainEvent_v1(BudgetPlanNeedAddedDomainEvent_v1 $event): void
    {
        $this->budgetPlanNeedEntryViewRepository->save(
            BudgetPlanNeedEntryView::fromBudgetPlanNeedAddedDomainEvent_v1($event),
        );
    }

    private function handleBudgetPlanNeedAdjustedDomainEvent_v1(BudgetPlanNeedAdjustedDomainEvent_v1 $event): void
    {
        $budgetPlanNeedView = $this->budgetPlanNeedEntryViewRepository->findOneByUuid($event->uuid);

        if (!$budgetPlanNeedView instanceof BudgetPlanNeedEntryViewInterface) {
            return;
        }

        $budgetPlanNeedView->fromEvent($event);
        $this->budgetPlanNeedEntryViewRepository->save($budgetPlanNeedView);
    }

    private function handleBudgetPlanNeedRemovedDomainEvent_v1(BudgetPlanNeedRemovedDomainEvent_v1 $event): void
    {
        $this->budgetPlanNeedEntryViewRepository->delete($event->uuid);
    }

    private function handleBudgetPlanRemovedDomainEvent_v1(BudgetPlanRemovedDomainEvent_v1 $event): void
    {
        $this->budgetPlanNeedEntryViewRepository->deleteByBudgetPlanId($event->aggregateId);
    }
}
