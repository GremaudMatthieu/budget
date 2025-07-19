<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanCurrencyChangedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\Gateway\BudgetPlan\Views\BudgetPlanView;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetPlanProjection
{
    public function __construct(
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
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
            BudgetPlanRemovedDomainEvent_v1::class => $this->handleBudgetPlanRemovedDomainEvent_v1($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1($event),
            BudgetPlanCurrencyChangedDomainEvent_v1::class => $this->handleBudgetPlanCurrencyChangedDomainEvent_v1($event),
            BudgetPlanIncomeAddedDomainEvent_v1::class => $this->handleBudgetPlanIncomeAddedDomainEvent_v1($event),
            BudgetPlanSavingAddedDomainEvent_v1::class => $this->handleBudgetPlanSavingAddedDomainEvent_v1($event),
            BudgetPlanNeedAddedDomainEvent_v1::class => $this->handleBudgetPlanNeedAddedDomainEvent_v1($event),
            BudgetPlanWantAddedDomainEvent_v1::class => $this->handleBudgetPlanWantAddedDomainEvent_v1($event),
            BudgetPlanIncomeAdjustedDomainEvent_v1::class => $this->handleBudgetPlanIncomeAdjustedDomainEvent_v1($event),
            BudgetPlanNeedAdjustedDomainEvent_v1::class => $this->handleBudgetPlanNeedAdjustedDomainEvent_v1($event),
            BudgetPlanSavingAdjustedDomainEvent_v1::class => $this->handleBudgetPlanSavingAdjustedDomainEvent_v1($event),
            BudgetPlanWantAdjustedDomainEvent_v1::class => $this->handleBudgetPlanWantAdjustedDomainEvent_v1($event),
            BudgetPlanIncomeRemovedDomainEvent_v1::class => $this->handleBudgetPlanIncomeRemovedDomainEvent_v1($event),
            BudgetPlanWantRemovedDomainEvent_v1::class => $this->handleBudgetPlanWantRemovedDomainEvent_v1($event),
            BudgetPlanNeedRemovedDomainEvent_v1::class => $this->handleBudgetPlanNeedRemovedDomainEvent_v1($event),
            BudgetPlanSavingRemovedDomainEvent_v1::class => $this->handleBudgetPlanSavingRemovedDomainEvent_v1($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent_v1(BudgetPlanGeneratedDomainEvent_v1 $event): void
    {
        $this->budgetPlanViewRepository->save(BudgetPlanView::fromBudgetPlanGeneratedDomainEvent_v1($event));
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1 $event,
    ): void {
        $this->budgetPlanViewRepository->save(
            BudgetPlanView::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1($event),
        );
    }

    private function handleBudgetPlanRemovedDomainEvent_v1(BudgetPlanRemovedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanCurrencyChangedDomainEvent_v1(BudgetPlanCurrencyChangedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanIncomeAddedDomainEvent_v1(BudgetPlanIncomeAddedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingAddedDomainEvent_v1(BudgetPlanSavingAddedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedAddedDomainEvent_v1(BudgetPlanNeedAddedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantAddedDomainEvent_v1(BudgetPlanWantAddedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanIncomeAdjustedDomainEvent_v1(BudgetPlanIncomeAdjustedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedAdjustedDomainEvent_v1(BudgetPlanNeedAdjustedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingAdjustedDomainEvent_v1(BudgetPlanSavingAdjustedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantAdjustedDomainEvent_v1(BudgetPlanWantAdjustedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanIncomeRemovedDomainEvent_v1(BudgetPlanIncomeRemovedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantRemovedDomainEvent_v1(BudgetPlanWantRemovedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedRemovedDomainEvent_v1(BudgetPlanNeedRemovedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingRemovedDomainEvent_v1(BudgetPlanSavingRemovedDomainEvent_v1 $event): void
    {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($event);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }
}
