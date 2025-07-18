<?php

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Projections\BudgetEnvelopeLedgerEntryProjection;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeLedgerEntryView;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
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
        $event = new BudgetEnvelopeCreditedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );
        $envelopeHistory = BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeCreditedDomainEvent_v1($event);

        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
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
        $envelopeView->fromEvent(new BudgetEnvelopeCreditedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        ));

        $envelopeHistory = BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeDebitedDomainEvent_v1($event);

        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
    }
}
