<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent_v1;
use PHPUnit\Framework\TestCase;

class UserLastnameChangedEvent_v1Test extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserLastnameChangedDomainEvent_v1(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'Doe',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('Doe', $array['lastname']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'requestId' => '8f636cef-6a4d-40f1-a9cf-4e64f67ce7c0',
            'lastname' => 'Doe',
            'occurredOn' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
        ];

        $event = UserLastnameChangedDomainEvent_v1::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['requestId'], $event->requestId);
        $this->assertEquals($data['lastname'], $event->lastname);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
