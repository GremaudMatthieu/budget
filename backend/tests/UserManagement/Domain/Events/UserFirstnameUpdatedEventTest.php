<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Domain\Events;

use App\UserManagement\Domain\Events\UserFirstnameUpdatedEvent;
use PHPUnit\Framework\TestCase;

class UserFirstnameUpdatedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserFirstnameUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'John');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('John', $array['firstname']);
        $this->assertEquals($event->occurredOn()->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'firstname' => 'John',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserFirstnameUpdatedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->getAggregateId());
        $this->assertEquals($data['firstname'], $event->getFirstname());
        $this->assertEquals($data['occurredOn'], $event->occurredOn()->format(\DateTimeInterface::ATOM));
    }
}
