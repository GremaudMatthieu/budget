<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Domain\Events;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedDomainEvent;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeTargetedAmountUpdatedDomainEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new BudgetEnvelopeTargetedAmountUpdatedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('1ced5c7e-fd3a-4a36-808e-75ddc478f67b', $array['userId']);
        $this->assertEquals('500.00', $array['targetedAmount']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'targetedAmount' => '500.00',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = BudgetEnvelopeTargetedAmountUpdatedDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['targetedAmount'], $event->targetedAmount);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}