<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserDeletedDomainEvent_v1;
use PHPUnit\Framework\TestCase;

class UserDeletedEvent_v1Test extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserDeletedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'requestId' => '8f636cef-6a4d-40f1-a9cf-4e64f67ce7c0',
            'occurredOn' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
        ];

        $event = UserDeletedDomainEvent_v1::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['requestId'], $event->requestId);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
