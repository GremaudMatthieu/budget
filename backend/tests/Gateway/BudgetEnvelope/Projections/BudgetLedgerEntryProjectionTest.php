<?php

namespace App\Tests\Gateway\BudgetEnvelope\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;
use App\Presentation\BudgetEnvelope\Projections\BudgetEnvelopeLedgerEntryProjection;
use App\Presentation\BudgetEnvelope\Views\BudgetEnvelopeLedgerEntryView;
use App\Presentation\BudgetEnvelope\Views\BudgetEnvelopeView;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptor;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepository;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMap;
use App\Presentation\BudgetEnvelope\BudgetEnvelopeLedgerEntryFinder;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BudgetLedgerEntryProjectionTest extends TestCase
{
    private BudgetEnvelopeLedgerEntryFinder&MockObject $budgetEnvelopeLedgerEntryViewRepository;
    private EventSourcedRepository&MockObject $eventSourcedRepository;
    private BudgetEnvelopeLedgerEntryProjection $budgetEnvelopeLedgerEntryProjection;
    private EventClassMap&MockObject $eventClassMap;
    private KeyManagementRepository&MockObject $keyManagementRepository;
    private EventEncryptor&MockObject $eventEncryptor;

    protected function setUp(): void
    {
        $this->budgetEnvelopeLedgerEntryViewRepository = $this->createMock(BudgetEnvelopeLedgerEntryFinder::class);
        $this->eventSourcedRepository = $this->createMock(EventSourcedRepository::class);
        $this->eventClassMap = $this->createMock(EventClassMap::class);
        $this->eventEncryptor = $this->createMock(EventEncryptor::class);
        $this->keyManagementRepository = $this->createMock(KeyManagementRepository::class);
        $this->budgetEnvelopeLedgerEntryProjection = new BudgetEnvelopeLedgerEntryProjection(
            $this->budgetEnvelopeLedgerEntryViewRepository,
            $this->eventSourcedRepository,
            $this->eventClassMap,
            $this->keyManagementRepository,
            $this->eventEncryptor,
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

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

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

        $this->keyManagementRepository->method('getKey')
            ->with('1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn('encryption-key');

        $this->eventEncryptor->method('decrypt')
            ->with($event, '1ced5c7e-fd3a-4a36-808e-75ddc478f67b')
            ->willReturn($event);

        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
    }
}
