<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use PHPUnit\Framework\TestCase;

class UserReplayedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserReplayedDomainEvent(
            'aggregateId',
            'firstname',
            'lastname',
            'fr',
            'email',
            true,
            '2021-09-01T00:00:00+00:00',
            '2021-09-01T00:00:00+00:00',
            'aggregateId',
            'google',
            '1234567890',
        );

        $this->assertEquals(
            [
                'aggregateId' => 'aggregateId',
                'requestId' => DomainEventInterface::DEFAULT_REQUEST_ID,
                'userId' => 'aggregateId',
                'email' => 'email',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'languagePreference' => 'fr',
                'isConsentGiven' => true,
                'consentDate' => '2021-09-01T00:00:00+00:00',
                'updatedAt' => '2021-09-01T00:00:00+00:00',
                'registrationContext' => 'google',
                'providerUserId' => '1234567890',
                'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
            $event->toArray()
        );
    }

    public function testFromArray(): void
    {
        $event = UserReplayedDomainEvent::fromArray([
            'aggregateId' => 'aggregateId',
            'userId' => 'aggregateId',
            'requestId' => '8f636cef-6a4d-40f1-a9cf-4e64f67ce7c0',
            'email' => 'email',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'languagePreference' => 'fr',
            'isConsentGiven' => true,
            'consentDate' => '2021-09-01T00:00:00+00:00',
            'updatedAt' => '2021-09-01T00:00:00+00:00',
            'registrationContext' => 'google',
            'providerUserId' => '1234567890',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        $this->assertEquals('aggregateId', $event->aggregateId);
        $this->assertEquals('aggregateId', $event->userId);
        $this->assertEquals('8f636cef-6a4d-40f1-a9cf-4e64f67ce7c0', $event->requestId);
        $this->assertEquals('firstname', $event->firstname);
        $this->assertEquals('lastname', $event->lastname);
        $this->assertEquals('fr', $event->languagePreference);
        $this->assertEquals('email', $event->email);
        $this->assertEquals('google', $event->registrationContext);
        $this->assertEquals('1234567890', $event->providerUserId);
        $this->assertTrue($event->isConsentGiven);
        $this->assertEquals('2021-09-01T00:00:00+00:00', $event->consentDate->format(\DateTimeInterface::ATOM));
        $this->assertEquals('2021-09-01T00:00:00+00:00', $event->updatedAt->format(\DateTimeInterface::ATOM));
    }
}
