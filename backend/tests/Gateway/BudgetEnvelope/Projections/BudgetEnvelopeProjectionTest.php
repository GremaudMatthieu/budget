<?php

namespace App\Tests\Gateway\BudgetEnvelope\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\Gateway\BudgetEnvelope\Projections\BudgetEnvelopeProjection;
use App\Gateway\BudgetEnvelope\Views\BudgetEnvelopeView;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeProjectionTest extends TestCase
{
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;
    private BudgetEnvelopeProjection $budgetEnvelopeProjection;
    private KeyManagementRepositoryInterface&MockObject $keyManagementRepository;
    private EventEncryptorInterface&MockObject $eventEncryptor;

    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->keyManagementRepository = $this->createMock(KeyManagementRepositoryInterface::class);
        $this->budgetEnvelopeProjection = new BudgetEnvelopeProjection(
            $this->envelopeViewRepository,
            $this->keyManagementRepository,
            $this->eventEncryptor,
        );
    }

    public function testHandleEnvelopeAddedEvent(): void
    {
        $event = new BudgetEnvelopeAddedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            'USD',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::BUDGET_ENVELOPE->value,
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn (BudgetEnvelopeView $view) => $view->uuid === $event->aggregateId
                    && $view->createdAt == $event->occurredOn
                    && $view->updatedAt == \DateTime::createFromImmutable($event->occurredOn)
                    && false === $view->isDeleted
                    && $view->targetedAmount === $event->targetedAmount
                    && '0.00' === $view->currentAmount
                    && $view->name === $event->name
                    && $view->userUuid === $event->userId
                    && $view->currency === $event->currency));

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedEvent(): void
    {
        $event = new BudgetEnvelopeCreditedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeCreditedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedEvent(): void
    {
        $event = new BudgetEnvelopeDebitedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDebitedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedEvent(): void
    {
        $event = new BudgetEnvelopeRenamedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeRenamedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedEvent(): void
    {
        $event = new BudgetEnvelopeDeletedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            true,
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDeletedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            true,
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeTargetedAmountUpdatedEvent(): void
    {
        $event = new BudgetEnvelopeTargetedAmountChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '1000.00',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCurrencyChangedEvent(): void
    {
        $event = new BudgetEnvelopeCurrencyChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'USD',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCurrencyChangedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeCurrencyChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'USD',
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeTargetedAmountUpdatedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeTargetedAmountChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '1000.00',
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeRewoundEvent(): void
    {
        $event = new BudgetEnvelopeRewoundDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            'USD',
            '2024-01-01 00:00:00',
            '2024-01-01 00:00:00',
            false,
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeRewoundWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeRewoundDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            'USD',
            '2024-01-01 00:00:00',
            '2024-01-01 00:00:00',
            false,
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeReplayedEvent(): void
    {
        $event = new BudgetEnvelopeReplayedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            'USD',
            '2024-01-01 00:00:00',
            false,
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeReplayedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeReplayedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            'USD',
            '2024-01-01 00:00:00',
            false,
        );

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }
}
