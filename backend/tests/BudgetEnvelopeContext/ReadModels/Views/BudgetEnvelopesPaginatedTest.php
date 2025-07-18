<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopesPaginated;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\SharedContext\Domain\Enums\ContextEnum;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopesPaginatedTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $envelope1 = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope 1',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );
        $envelope2 = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope 2',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $envelopes = [$envelope1, $envelope2];
        $totalItems = 2;
        $envelopesPaginated = new BudgetEnvelopesPaginated($envelopes, $totalItems);

        $expected = [
            'envelopes' => $envelopes,
            'totalItems' => $totalItems,
        ];

        $this->assertEquals($expected, $envelopesPaginated->jsonSerialize());
    }

    public function testGetEnvelopes(): void
    {
        $envelopes = [$this->createMock(\stdClass::class)];
        $envelopesPaginated = new BudgetEnvelopesPaginated($envelopes, 1);
        $this->assertEquals($envelopes, $envelopesPaginated->budgetEnvelopes);
    }

    public function testGetTotalItems(): void
    {
        $envelopes = [$this->createMock(\stdClass::class)];
        $envelopesPaginated = new BudgetEnvelopesPaginated($envelopes, 1);
        $this->assertEquals(1, $envelopesPaginated->totalItems);
    }
}
