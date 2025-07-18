<?php

declare(strict_types=1);

namespace App\Tests\Gateway\BudgetEnvelope\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\Gateway\BudgetEnvelope\Views\BudgetEnvelopeView;
use App\SharedContext\Domain\Enums\ContextEnum;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeViewTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $envelopeView->fromEvent(
            new BudgetEnvelopeCreditedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                '500.00',
                'test',
            ),
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'currentAmount' => '500',
            'targetedAmount' => '1000.00',
            'name' => 'Test Envelope',
            'currency' => 'USD',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }

    public function testApplyAddedEvent(): void
    {
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent_v1(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $envelopeView->fromEvent(
            new BudgetEnvelopeAddedDomainEvent_v1(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
                'USD',
                'b7e685be-db83-4866-9f85-102fac30a50b',
                ContextEnum::BUDGET_ENVELOPE->value,
            ),
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'currentAmount' => '0.00',
            'targetedAmount' => '1000.00',
            'name' => 'Test Envelope',
            'currency' => 'USD',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }
}
