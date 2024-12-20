<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\Application\Handlers\QueryHandlers;

use App\EnvelopeManagement\Application\Handlers\QueryHandlers\ShowEnvelopeQueryHandler;
use App\EnvelopeManagement\Application\Queries\ShowEnvelopeQuery;
use App\EnvelopeManagement\Domain\Exceptions\EnvelopeNotFoundException;
use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeViewRepositoryInterface;
use App\EnvelopeManagement\Domain\Ports\Outbound\QueryBusInterface;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShowEnvelopeQueryHandlerTest extends TestCase
{
    private ShowEnvelopeQueryHandler $showEnvelopeQueryHandler;
    private QueryBusInterface&MockObject $queryBus;
    private EnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(EnvelopeViewRepositoryInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);

        $this->showEnvelopeQueryHandler = new ShowEnvelopeQueryHandler(
            $this->envelopeViewRepository,
        );
    }

    public function testShowEnvelopeSuccess(): void
    {
        $envelopeView = EnvelopeView::createFromRepository(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'Electricity',
                'target_budget' => '300.00',
                'current_budget' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'is_deleted' => false,
            ],
        );
        $showEnvelopeQuery = new ShowEnvelopeQuery($envelopeView->getUuid(), 'd26cc02e-99e7-428c-9d61-572dff3f84a7');

        $this->envelopeViewRepository->expects($this->once())->method('findOneBy')->willReturn($envelopeView);

        $envelope = $this->showEnvelopeQueryHandler->__invoke($showEnvelopeQuery);

        $this->assertEquals($envelopeView, $envelope);
    }

    public function testShowEnvelopeReturnsNull(): void
    {
        $envelopeView = EnvelopeView::createFromRepository(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'Electricity',
                'target_budget' => '300.00',
                'current_budget' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'is_deleted' => false,
            ],
        );
        $showEnvelopeQuery = new ShowEnvelopeQuery($envelopeView->getUuid(), 'd26cc02e-99e7-428c-9d61-572dff3f84a7');

        $this->envelopeViewRepository->expects($this->once())->method('findOneBy')->willReturn(null);
        $this->expectException(EnvelopeNotFoundException::class);
        $this->expectExceptionMessage('Envelope not found');

        $this->showEnvelopeQueryHandler->__invoke($showEnvelopeQuery);
    }
}
