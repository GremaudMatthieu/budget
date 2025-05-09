<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use PHPUnit\Framework\TestCase;

class UserSignedUpEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserSignedUpDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'test@example.com',
            'John',
            'Doe',
            'fr',
            true,
            ['ROLE_USER'],
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'google',
            '1234567890',

        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['userId']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('google', $array['registrationContext']);
        $this->assertEquals('1234567890', $array['providerUserId']);
        $this->assertEquals('John', $array['firstname']);
        $this->assertEquals('Doe', $array['lastname']);
        $this->assertTrue($array['isConsentGiven']);
        $this->assertEquals(['ROLE_USER'], $array['roles']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'email' => 'test@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'languagePreference' => 'fr',
            'isConsentGiven' => true,
            'roles' => ['ROLE_USER'],
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'registrationContext' => 'google',
            'providerUserId' => '1234567890',
            'userId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'requestId' => '8f636cef-6a4d-40f1-a9cf-4e64f67ce7c0',
        ];

        $event = UserSignedUpDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['email'], $event->email);
        $this->assertEquals($data['registrationContext'], $event->registrationContext);
        $this->assertEquals($data['providerUserId'], $event->providerUserId);
        $this->assertEquals($data['firstname'], $event->firstname);
        $this->assertEquals($data['lastname'], $event->lastname);
        $this->assertEquals($data['languagePreference'], $event->languagePreference);
        $this->assertEquals($data['isConsentGiven'], $event->isConsentGiven);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['requestId'], $event->requestId);
        $this->assertEquals($data['roles'], $event->roles);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
