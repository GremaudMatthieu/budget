<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeContext\Application\Handlers\QueryHandlers\GetABudgetEnvelopeWithItsLedgerQueryHandler;
use App\BudgetEnvelopeContext\Application\Queries\GetABudgetEnvelopeWithItsLedgerQuery;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryType;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\SharedContext\Domain\Enums\ContextEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetABudgetEnvelopeWithItsLedgerQueryHandlerTest extends TestCase
{
    private GetABudgetEnvelopeWithItsLedgerQueryHandler $getABudgetEnvelopeWithItsLedgerQueryHandler;
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);

        $this->getABudgetEnvelopeWithItsLedgerQueryHandler = new GetABudgetEnvelopeWithItsLedgerQueryHandler(
            $this->envelopeViewRepository,
        );
    }

    public function testGetABudgetEnvelopeWithItsHistorySuccess(): void
    {
        $envelopeView = BudgetEnvelopeView::fromRepository(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'Electricity',
                'targeted_amount' => '300.00',
                'current_amount' => '150.00',
                'currency' => 'USD',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'updated_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'context_uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'context' => ContextEnum::BUDGET_ENVELOPE->value,
                'is_deleted' => false,
            ],
        );
        $getABudgetEnvelopeWithItsHistoryQuery = new GetABudgetEnvelopeWithItsLedgerQuery(
            BudgetEnvelopeId::fromString($envelopeView->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
        );
        $envelopeHistoryCreatedAt = new \DateTimeImmutable();
        $envelopeCreatedAt = new \DateTime()->format('Y-m-d H:i:s');

        $this->envelopeViewRepository->expects($this->once())->method('findOneEnvelopeWithItsLedgerBy')
            ->willReturn(
                [
                    'envelope' => [
                        'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                        'name' => 'Electricity',
                        'targeted_amount' => '300.00',
                        'current_amount' => '150.00',
                        'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                        'currency' => 'USD',
                        'created_at' => $envelopeCreatedAt,
                        'updated_at' => $envelopeCreatedAt,
                        'is_deleted' => false,
                    ],
                    'history' => [
                        [
                            'transaction_type' => BudgetEnvelopeEntryType::DEBIT,
                            'aggregate_id' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                            'monetary_amount' => '150.00',
                            'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                            'created_at' => $envelopeHistoryCreatedAt,
                        ],
                    ],
                ],
            );

        $envelopeWithHistory = $this->getABudgetEnvelopeWithItsLedgerQueryHandler->__invoke($getABudgetEnvelopeWithItsHistoryQuery);

        $this->assertEquals([
            'envelope' => [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'Electricity',
                'targeted_amount' => '300.00',
                'current_amount' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'currency' => 'USD',
                'created_at' => $envelopeCreatedAt,
                'updated_at' => $envelopeCreatedAt,
                'is_deleted' => false,
            ],
            'history' => [
                [
                    'transaction_type' => BudgetEnvelopeEntryType::DEBIT,
                    'aggregate_id' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                    'monetary_amount' => '150.00',
                    'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                    'created_at' => $envelopeHistoryCreatedAt,
                ],
            ],
        ], $envelopeWithHistory);
    }

    public function testGetABudgetEnvelopeWithItsHistoryReturnsNull(): void
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
        $getABudgetEnvelopeWithItsHistoryQuery = new GetABudgetEnvelopeWithItsLedgerQuery(
            BudgetEnvelopeId::fromString($envelopeView->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
        );

        $this->envelopeViewRepository->expects($this->once())->method('findOneEnvelopeWithItsLedgerBy')->willReturn([]);
        $this->expectException(BudgetEnvelopeNotFoundException::class);
        $this->expectExceptionMessage('envelopes.notFound');

        $this->getABudgetEnvelopeWithItsLedgerQueryHandler->__invoke($getABudgetEnvelopeWithItsHistoryQuery);
    }
}
