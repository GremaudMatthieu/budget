<?php

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Projections\BudgetEnvelopeProjection;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeProjectionTest extends TestCase
{
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;
    private BudgetEnvelopeProjection $budgetEnvelopeProjection;

    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->budgetEnvelopeProjection = new BudgetEnvelopeProjection($this->envelopeViewRepository);
    }

    public function testHandleEnvelopeAddedEvent(): void
    {
        $event = new BudgetEnvelopeAddedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            'USD',
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (BudgetEnvelopeView $view) use ($event) {
                return $view->uuid === $event->aggregateId
                    && $view->createdAt == $event->occurredOn
                    && $view->updatedAt == \DateTime::createFromImmutable($event->occurredOn)
                    && false === $view->isDeleted
                    && $view->targetedAmount === $event->targetedAmount
                    && '0.00' === $view->currentAmount
                    && $view->name === $event->name
                    && $view->userUuid === $event->userId
                    && $view->currency === $event->currency;
            }));

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedEvent(): void
    {
        $event = new BudgetEnvelopeCreditedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeCreditedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedEvent(): void
    {
        $event = new BudgetEnvelopeDebitedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDebitedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedEvent(): void
    {
        $event = new BudgetEnvelopeRenamedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeRenamedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedEvent(): void
    {
        $event = new BudgetEnvelopeDeletedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            true,
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDeletedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            true,
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeTargetedAmountUpdatedEvent(): void
    {
        $event = new BudgetEnvelopeTargetedAmountChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '1000.00',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCurrencyChangedEvent(): void
    {
        $event = new BudgetEnvelopeCurrencyChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'USD',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCurrencyChangedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeCurrencyChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'USD',
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }


    public function testHandleEnvelopeTargetedAmountUpdatedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeTargetedAmountChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '1000.00',
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeRewoundEvent(): void
    {
        $event = new BudgetEnvelopeRewoundDomainEvent(
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
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeRewoundWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeRewoundDomainEvent(
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

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeReplayedEvent(): void
    {
        $event = new BudgetEnvelopeReplayedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            'USD',
            '2024-01-01 00:00:00',
            false,
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
                'USD',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeReplayedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeReplayedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            'USD',
            '2024-01-01 00:00:00',
            false,
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }
}
