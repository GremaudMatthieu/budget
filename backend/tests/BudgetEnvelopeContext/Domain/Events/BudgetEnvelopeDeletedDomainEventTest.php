<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Domain\Events;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeDeletedDomainEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new BudgetEnvelopeDeletedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            true,
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('1ced5c7e-fd3a-4a36-808e-75ddc478f67b', $array['userId']);
        $this->assertTrue($array['isDeleted']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'isDeleted' => true,
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = BudgetEnvelopeDeletedDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertTrue($event->isDeleted);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}