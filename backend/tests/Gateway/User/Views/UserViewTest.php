<?php

declare(strict_types=1);

namespace App\Tests\Gateway\User\Views;

use App\Gateway\User\Views\UserView;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent_v1;
use PHPUnit\Framework\TestCase;

class UserViewTest extends TestCase
{
    public function testUserView(): void
    {
        $userView = new UserView(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'john.doe@example.com',
            'John',
            'Doe',
            'fr',
            true,
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTime('2023-01-01T00:00:00+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $userView->uuid);
        $this->assertEquals('john.doe@example.com', $userView->getEmail());
        $this->assertEquals('John', $userView->firstname);
        $this->assertEquals('Doe', $userView->lastname);
        $this->assertEquals('fr', $userView->languagePreference);
        $this->assertTrue($userView->consentGiven);
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->consentDate->format(\DateTimeInterface::ATOM));
        $this->assertEquals(['ROLE_USER'], $userView->getRoles());
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->createdAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->updatedAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals('john.doe@example.com', $userView->getUserIdentifier());
        $this->assertEquals('google', $userView->registrationContext);
        $this->assertEquals('1234567890', $userView->providerUserId);
    }

    public function testJsonSerialize(): void
    {
        $userView = new UserView(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'john.doe@example.com',
            'John',
            'Doe',
            'fr',
            true,
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTime('2023-01-01T00:00:00+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'languagePreference' => 'fr',
            'email' => 'john.doe@example.com',
        ];

        $this->assertEquals($expected, $userView->jsonSerialize());
    }

    public function testCreateFromRepository(): void
    {
        $userData = [
            'id' => 1,
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'email' => 'john.doe@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'language_preference' => 'fr',
            'consent_given' => true,
            'consent_date' => '2023-01-01T00:00:00+00:00',
            'created_at' => '2023-01-01T00:00:00+00:00',
            'updated_at' => '2023-01-01T00:00:00+00:00',
            'roles' => json_encode(['ROLE_USER']),
            'registration_context' => 'google',
            'provider_user_id' => '1234567890',
            'context_uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'context' => ContextEnum::USER->value,
        ];

        $userView = UserView::fromRepository($userData);

        $this->assertEquals($userData['uuid'], $userView->getUuid());
        $this->assertEquals($userData['email'], $userView->getEmail());
        $this->assertEquals($userData['registration_context'], $userView->registrationContext);
        $this->assertEquals($userData['provider_user_id'], $userView->providerUserId);
        $this->assertEquals($userData['firstname'], $userView->firstname);
        $this->assertEquals($userData['lastname'], $userView->lastname);
        $this->assertEquals($userData['language_preference'], $userView->languagePreference);
        $this->assertEquals($userData['consent_given'], $userView->consentGiven);
        $this->assertEquals($userData['consent_date'], $userView->consentDate->format(\DateTimeInterface::ATOM));
        $this->assertEquals($userData['created_at'], $userView->createdAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals($userData['updated_at'], $userView->updatedAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals(json_decode($userData['roles'], true), $userView->getRoles());
    }

    public function testFromEvents(): void
    {
        $userView = new UserView(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'john.doe@example.com',
            'John',
            'Doe',
            'fr',
            true,
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTime('2023-01-01T00:00:00+00:00'),
            ['ROLE_USER'],
            'google',
            '1234567890',
            'b7e685be-db83-4866-9f85-102fac30a50b',
            ContextEnum::USER->value,
        );

        $userView->fromEvents(
            (function () {
                yield [
                    'type' => UserSignedUpDomainEvent_v1::class,
                    'payload' => json_encode([
                        'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
                        'userId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
                        'requestId' => '8f636cef-6a4d-40f1-a9cf-4e64f67ce7c0',
                        'email' => 'john.doe@example.com',
                        'firstname' => 'John',
                        'lastname' => 'Doe',
                        'languagePreference' => 'fr',
                        'isConsentGiven' => true,
                        'occurredOn' => '2023-01-01T00:00:00+00:00',
                        'roles' => ['ROLE_USER'],
                        'registrationContext' => 'google',
                        'providerUserId' => '1234567890',
                        'contextId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
                        'context' => ContextEnum::USER->value,
                    ]),
                ];
            })()
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'languagePreference' => 'fr',
            'email' => 'john.doe@example.com',
        ];

        $this->assertEquals($expected, $userView->jsonSerialize());
    }
}
