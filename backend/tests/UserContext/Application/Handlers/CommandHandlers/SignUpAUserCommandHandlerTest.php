<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\UserContext\Application\Commands\SignUpAUserCommand;
use App\UserContext\Application\Handlers\CommandHandlers\SignUpAUserCommandHandler;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Aggregates\UserEmailRegistry;
use App\UserContext\Domain\Exceptions\UserAlreadyExistsException;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserEmailRegistryId;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserRegistrationContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignUpAUserCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private SignUpAUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new SignUpAUserCommandHandler(
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

        $command = new SignUpAUserCommand(
            UserId::fromString($userId),
            UserEmail::fromString($email),
            UserFirstname::fromString($firstname),
            UserLastname::fromString($lastname),
            UserLanguagePreference::fromString($languagePreference),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
        );

        $registry = UserEmailRegistry::create(
            UserEmailRegistryId::fromString(UserEmailRegistry::DEFAULT_ID)
        );

        $this->eventStore->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function($id) use ($userId, $registry) {
                if ($id === $userId) {
                    throw new EventsNotFoundForAggregateException();
                }
                if ($id === UserEmailRegistry::DEFAULT_ID) {
                    return $registry;
                }
                throw new \RuntimeException("Unexpected ID: $id");
            });

        $this->eventStore->expects($this->once())
            ->method('trackAggregates')
            ->with($this->callback(function($aggregates) {
                return count($aggregates) === 2 &&
                    $aggregates[0] instanceof UserEmailRegistry &&
                    $aggregates[1] instanceof User;
            }));

        $this->handler->__invoke($command);
    }

    public function testSignUpUserAlreadyExists(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $email = 'test@example.com';
        $firstname = 'John';
        $lastname = 'Doe';
        $languagePreference = 'en';

        $command = new SignUpAUserCommand(
            UserId::fromString($userId),
            UserEmail::fromString($email),
            UserFirstname::fromString($firstname),
            UserLastname::fromString($lastname),
            UserLanguagePreference::fromString($languagePreference),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
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
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willReturn($existingUser);

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(UserAlreadyExistsException::class);
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

        $command = new SignUpAUserCommand(
            UserId::fromString($userId),
            UserEmail::fromString($email),
            UserFirstname::fromString($firstname),
            UserLastname::fromString($lastname),
            UserLanguagePreference::fromString($languagePreference),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
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
                'userId' => $existingUserId
            ]
        ]);

        $this->eventStore->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function($id) use ($userId, $registry) {
                if ($id === $userId) {
                    throw new EventsNotFoundForAggregateException();
                }
                if ($id === UserEmailRegistry::DEFAULT_ID) {
                    return $registry;
                }
                throw new \RuntimeException("Unexpected ID: $id");
            });

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(UserAlreadyExistsException::class);
        $this->handler->__invoke($command);
    }
}
