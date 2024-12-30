<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Domain\Events;

use App\UserManagement\Domain\Events\UserDeletedEvent;
use PHPUnit\Framework\TestCase;

class UserDeletedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals($event->occurredOn()->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserDeletedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->getAggregateId());
        $this->assertEquals($data['occurredOn'], $event->occurredOn()->format(\DateTimeInterface::ATOM));
    }
}
