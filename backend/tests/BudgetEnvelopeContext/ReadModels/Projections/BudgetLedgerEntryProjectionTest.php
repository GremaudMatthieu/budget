<?php

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Projections\BudgetEnvelopeLedgerEntryProjection;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeLedgerEntryView;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BudgetLedgerEntryProjectionTest extends TestCase
{
    private BudgetEnvelopeLedgerEntryViewRepositoryInterface&MockObject $budgetEnvelopeLedgerEntryViewRepository;
    private EventSourcedRepositoryInterface&MockObject $eventSourcedRepository;
    private BudgetEnvelopeLedgerEntryProjection $budgetEnvelopeLedgerEntryProjection;
    private EventClassMapInterface&MockObject $eventClassMap;

    protected function setUp(): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository = $this->createMock(BudgetEnvelopeLedgerEntryViewRepositoryInterface::class);
        $this->eventSourcedRepository = $this->createMock(EventSourcedRepositoryInterface::class);
        $this->eventClassMap = $this->createMock(EventClassMapInterface::class);
        $this->budgetEnvelopeLedgerEntryProjection = new BudgetEnvelopeLedgerEntryProjection(
            $this->budgetEnvelopeLedgerEntryViewRepository,
            $this->eventSourcedRepository,
            $this->eventClassMap,
        );
    }

    public function testHandleEnvelopeCreditedEvent(): void
    {
        $event = new BudgetEnvelopeCreditedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );
        $envelopeHistory = BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeCreditedDomainEvent($event);

        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
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
        $envelopeView->fromEvent(new BudgetEnvelopeCreditedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        ));

        $envelopeHistory = BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeDebitedDomainEvent($event);

        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
    }
}
