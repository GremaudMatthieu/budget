<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\Gateway\BudgetEnvelope\Views\BudgetEnvelopeView;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class BudgetEnvelopeProjection
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
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
            BudgetEnvelopeAddedDomainEvent_v1::class => $this->handleBudgetEnvelopeAddedDomainEvent_v1($event),
            BudgetEnvelopeCreditedDomainEvent_v1::class => $this->handleBudgetEnvelopeCreditedDomainEvent_v1($event),
            BudgetEnvelopeDebitedDomainEvent_v1::class => $this->handleBudgetEnvelopeDebitedDomainEvent_v1($event),
            BudgetEnvelopeRenamedDomainEvent_v1::class => $this->handleBudgetEnvelopeNamedDomainEvent($event),
            BudgetEnvelopeDeletedDomainEvent_v1::class => $this->handleBudgetEnvelopeDeletedDomainEvent_v1($event),
            BudgetEnvelopeRewoundDomainEvent_v1::class => $this->handleBudgetEnvelopeRewoundDomainEvent_v1($event),
            BudgetEnvelopeReplayedDomainEvent_v1::class => $this->handleBudgetEnvelopeReplayedDomainEvent_v1($event),
            BudgetEnvelopeTargetedAmountChangedDomainEvent_v1::class => $this->handleBudgetEnvelopeTargetedAmountChangedDomainEvent_v1($event),
            BudgetEnvelopeCurrencyChangedDomainEvent_v1::class => $this->handleBudgetEnvelopeCurrencyChangedDomainEvent_v1($event),
            default => null,
        };
    }

    private function handleBudgetEnvelopeAddedDomainEvent_v1(BudgetEnvelopeAddedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeViewRepository->save(BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1($event));
    }

    private function handleBudgetEnvelopeCreditedDomainEvent_v1(BudgetEnvelopeCreditedDomainEvent_v1 $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeDebitedDomainEvent_v1(BudgetEnvelopeDebitedDomainEvent_v1 $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeNamedDomainEvent(BudgetEnvelopeRenamedDomainEvent_v1 $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeDeletedDomainEvent_v1(BudgetEnvelopeDeletedDomainEvent_v1 $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeRewoundDomainEvent_v1(BudgetEnvelopeRewoundDomainEvent_v1 $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeReplayedDomainEvent_v1(BudgetEnvelopeReplayedDomainEvent_v1 $event): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeTargetedAmountChangedDomainEvent_v1(
        BudgetEnvelopeTargetedAmountChangedDomainEvent_v1 $event,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeCurrencyChangedDomainEvent_v1(
        BudgetEnvelopeCurrencyChangedDomainEvent_v1 $event,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $event->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($event);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }
}
