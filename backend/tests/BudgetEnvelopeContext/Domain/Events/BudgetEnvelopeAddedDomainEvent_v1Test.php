<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Domain\Events;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\SharedContext\Domain\Enums\ContextEnum;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeAddedDomainEvent_v1Test extends TestCase
{
    public function testToArray(): void
    {
        $event = new BudgetEnvelopeAddedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            'USD',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::BUDGET_ENVELOPE->value,
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('1ced5c7e-fd3a-4a36-808e-75ddc478f67b', $array['userId']);
        $this->assertEquals('Test', $array['name']);
        $this->assertEquals('1000.00', $array['targetedAmount']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'name' => 'Test',
            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
            'targetedAmount' => '1000.00',
            'currency' => 'USD',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'context' => ContextEnum::BUDGET_ENVELOPE->value,
            'contextId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
        ];

        $event = BudgetEnvelopeAddedDomainEvent_v1::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['name'], $event->name);
        $this->assertEquals($data['targetedAmount'], $event->targetedAmount);
        $this->assertEquals($data['currency'], $event->currency);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
