<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeContext\Application\Handlers\QueryHandlers\ListBudgetEnvelopesQueryHandler;
use App\BudgetEnvelopeContext\Application\Queries\ListBudgetEnvelopesQuery;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\ListBudgetEnvelopesInput;
use App\Gateway\BudgetEnvelope\Views\BudgetEnvelopesPaginated;
use App\Gateway\BudgetEnvelope\Views\BudgetEnvelopeView;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListBudgetEnvelopesQueryHandlerTest extends TestCase
{
    private ListBudgetEnvelopesQueryHandler $listBudgetEnvelopesQueryHandler;
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);

        $this->listBudgetEnvelopesQueryHandler = new ListBudgetEnvelopesQueryHandler(
            $this->envelopeViewRepository,
        );
    }

    public function testListBudgetEnvelopesSuccess(): void
    {
        $envelopeView = BudgetEnvelopeView::fromRepository(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'Electricity',
                'targeted_amount' => '300.00',
                'current_amount' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'currency' => 'USD',
                'created_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'updated_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'is_deleted' => false,
                'context_uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'context' => ContextEnum::BUDGET_ENVELOPE->value,
            ],
        );
        $envelopePaginated = new BudgetEnvelopesPaginated([$envelopeView], 1);
        $listBudgetEnvelopesInput = new ListBudgetEnvelopesInput([], 10, 0);
        $listBudgetEnvelopesQuery = new ListBudgetEnvelopesQuery(
            UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            $listBudgetEnvelopesInput->orderBy,
            $listBudgetEnvelopesInput->limit,
            $listBudgetEnvelopesInput->offset,
        );

        $this->envelopeViewRepository->expects($this->once())->method('findBy')->willReturn($envelopePaginated);

        $envelopePaginatedResult = $this->listBudgetEnvelopesQueryHandler->__invoke($listBudgetEnvelopesQuery);

        $this->assertEquals($envelopePaginated, $envelopePaginatedResult);
    }
}
