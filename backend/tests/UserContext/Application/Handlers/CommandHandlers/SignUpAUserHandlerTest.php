<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\UserContext\Application\Commands\SignUpAUser;
use App\UserContext\Application\Commands\SignUpAUserHandler;
use App\UserContext\Domain\Exceptions\UserAlreadyExists;
use App\UserContext\Domain\User;
use App\UserContext\Domain\UserConsent;
use App\UserContext\Domain\UserEmail;
use App\UserContext\Domain\UserEmailRegistry;
use App\UserContext\Domain\UserEmailRegistryId;
use App\UserContext\Domain\UserFirstname;
use App\UserContext\Domain\UserLastname;
use App\UserContext\Domain\UserRegistrationContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignUpAUserHandlerTest extends TestCase
{
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private SignUpAUserHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new SignUpAUserHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testSignUpUserSuccess(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $email = 'test@example.com';
        $firstname = 'John';
        $lastname = 'Doe';
        $languagePreference = 'en';

        $command = new SignUpAUser(
            UserId::fromString($userId),
            UserEmail::fromString($email),
            UserFirstname::fromString($firstname),
            UserLastname::fromString($lastname),
            UserLanguagePreference::fromString($languagePreference),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
            Context::from($userId, ContextEnum::USER->value),
        );

        $registry = UserEmailRegistry::create(
            UserEmailRegistryId::fromString(UserEmailRegistry::DEFAULT_ID)
        );

        $this->eventStore->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function ($id) use ($userId, $registry) {
                if ($id === $userId) {
                    throw new EventsNotFoundForAggregate();
                }
                if (UserEmailRegistry::DEFAULT_ID === $id) {
                    return $registry;
                }
                throw new \RuntimeException("Unexpected ID: $id");
            });

        $this->eventStore->expects($this->once())
            ->method('trackAggregates')
            ->with($this->callback(fn ($aggregates) => 2 === count($aggregates)
                    && $aggregates[0] instanceof UserEmailRegistry
                    && $aggregates[1] instanceof User));

        $this->handler->__invoke($command);
    }

    public function testSignUpUserAlreadyExists(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $email = 'test@example.com';
        $firstname = 'John';
        $lastname = 'Doe';
        $languagePreference = 'en';

        $command = new SignUpAUser(
            UserId::fromString($userId),
            UserEmail::fromString($email),
            UserFirstname::fromString($firstname),
            UserLastname::fromString($lastname),
            UserLanguagePreference::fromString($languagePreference),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
            Context::from($userId, ContextEnum::USER->value),
        );

        $existingUser = User::create(
            UserId::fromString($userId),
            UserEmail::fromString($email),
            UserFirstname::fromString($firstname),
            UserLastname::fromString($lastname),
            UserLanguagePreference::fromString($languagePreference),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
            Context::from($userId, ContextEnum::USER->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willReturn($existingUser);

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(UserAlreadyExists::class);
        $this->handler->__invoke($command);
    }

    public function testSignUpUserEmailAlreadyExists(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $email = 'existing@example.com';
        $existingUserId = '58a32191-3fa0-4477-8eb2-8dd3b0b7c123';
        $firstname = 'John';
        $lastname = 'Doe';
        $languagePreference = 'en';

        $command = new SignUpAUser(
            UserId::fromString($userId),
            UserEmail::fromString($email),
            UserFirstname::fromString($firstname),
            UserLastname::fromString($lastname),
            UserLanguagePreference::fromString($languagePreference),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
            Context::from($userId, ContextEnum::USER->value),
        );

        $registry = UserEmailRegistry::create(
            UserEmailRegistryId::fromString(UserEmailRegistry::DEFAULT_ID)
        );

        $reflection = new \ReflectionClass($registry);
        $emailHashesProperty = $reflection->getProperty('emailHashes');
        $emailHashesProperty->setAccessible(true);

        $emailHash = hash('sha256', strtolower($email));
        $emailHashesProperty->setValue($registry, [
            $emailHash => [
                'isRegistered' => true,
                'userId' => $existingUserId,
            ],
        ]);

        $this->eventStore->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function ($id) use ($userId, $registry) {
                if ($id === $userId) {
                    throw new EventsNotFoundForAggregate();
                }
                if (UserEmailRegistry::DEFAULT_ID === $id) {
                    return $registry;
                }
                throw new \RuntimeException("Unexpected ID: $id");
            });

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(UserAlreadyExists::class);
        $this->handler->__invoke($command);
    }
}
