<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeManagement\Application\Handlers\QueryHandlers\ListBudgetEnvelopesQueryHandler;
use App\BudgetEnvelopeManagement\Application\Queries\ListBudgetEnvelopesQuery;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\Domain\Ports\Outbound\QueryBusInterface;
use App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs\ListBudgetEnvelopesInput;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopesPaginated;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListBudgetEnvelopesQueryHandlerTest extends TestCase
{
    private ListBudgetEnvelopesQueryHandler $listBudgetEnvelopesQueryHandler;
    private QueryBusInterface&MockObject $queryBus;
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);

        $this->listBudgetEnvelopesQueryHandler = new ListBudgetEnvelopesQueryHandler(
            $this->envelopeViewRepository,
        );
    }

    public function testListBudgetEnvelopesSuccess(): void
    {
        $envelopeView = BudgetEnvelopeView::createFromRepository(
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
        $envelopePaginated = new BudgetEnvelopesPaginated([$envelopeView], 1);
        $listBudgetEnvelopesInput = new ListBudgetEnvelopesInput([], 10, 0);
        $listBudgetEnvelopesQuery = new ListBudgetEnvelopesQuery(
            'd26cc02e-99e7-428c-9d61-572dff3f84a7',
            $listBudgetEnvelopesInput->getOrderBy(),
            $listBudgetEnvelopesInput->getLimit(),
            $listBudgetEnvelopesInput->getOffset(),
        );

        $this->envelopeViewRepository->expects($this->once())->method('findBy')->willReturn($envelopePaginated);

        $envelopePaginatedResult = $this->listBudgetEnvelopesQueryHandler->__invoke($listBudgetEnvelopesQuery);

        $this->assertEquals($envelopePaginated, $envelopePaginatedResult);
    }
}