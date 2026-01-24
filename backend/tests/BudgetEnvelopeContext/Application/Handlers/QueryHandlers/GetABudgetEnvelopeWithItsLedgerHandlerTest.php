<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeEntryType;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFound;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\Views\BudgetEnvelopeView;
use App\Presentation\BudgetEnvelope\BudgetEnvelopeFinder;
use App\Presentation\BudgetEnvelope\Queries\GetABudgetEnvelopeWithItsLedger;
use App\Presentation\BudgetEnvelope\Queries\GetABudgetEnvelopeWithItsLedgerHandler;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetABudgetEnvelopeWithItsLedgerHandlerTest extends TestCase
{
    private GetABudgetEnvelopeWithItsLedgerHandler $getABudgetEnvelopeWithItsLedgerQueryHandler;
    private BudgetEnvelopeFinder&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeFinder::class);

        $this->getABudgetEnvelopeWithItsLedgerQueryHandler = new GetABudgetEnvelopeWithItsLedgerHandler(
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
        $getABudgetEnvelopeWithItsHistoryQuery = new GetABudgetEnvelopeWithItsLedger(
            BudgetEnvelopeId::fromString($envelopeView->uuid),
            UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
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
        $getABudgetEnvelopeWithItsHistoryQuery = new GetABudgetEnvelopeWithItsLedger(
            BudgetEnvelopeId::fromString($envelopeView->uuid),
            UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
        );

        $this->envelopeViewRepository->expects($this->once())->method('findOneEnvelopeWithItsLedgerBy')->willReturn([]);
        $this->expectException(BudgetEnvelopeNotFound::class);
        $this->expectExceptionMessage('envelopes.notFound');

        $this->getABudgetEnvelopeWithItsLedgerQueryHandler->__invoke($getABudgetEnvelopeWithItsHistoryQuery);
    }
}
