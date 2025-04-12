<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\UserContext\Application\Commands\DeleteAUserCommand;
use App\UserContext\Application\Handlers\CommandHandlers\DeleteAUserCommandHandler;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Aggregates\UserEmailRegistry;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserEmailRegistryId;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserRegistrationContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAUserCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private DeleteAUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new DeleteAUserCommandHandler(
            $this->eventSourcedRepository
        );
    }

    public function testDeleteUserSuccess(): void
    {
        $userId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';
        $userEmail = 'test@example.com';
        $command = new DeleteAUserCommand(UserId::fromString($userId));

        $user = User::create(
            UserId::fromString($userId),
            UserEmail::fromString($userEmail),
            UserFirstname::fromString('Test'),
            UserLastname::fromString('User'),
            UserLanguagePreference::fromString('fr'),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
        );

        $registry = UserEmailRegistry::create(
            UserEmailRegistryId::fromString(UserEmailRegistry::DEFAULT_ID)
        );

        $this->eventStore->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function($id) use ($userId, $user, $registry) {
                if ($id === $userId) {
                    return $user;
                }
                if ($id === UserEmailRegistry::DEFAULT_ID) {
                    return $registry;
                }
                throw new \RuntimeException("Unexpected ID: $id");
            });

        $this->handler->__invoke($command);
    }

    public function testDeleteUserNotFound(): void
    {
        $userId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';
        $command = new DeleteAUserCommand(UserId::fromString($userId));

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willThrowException(new EventsNotFoundForAggregateException());

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(EventsNotFoundForAggregateException::class);
        $this->handler->__invoke($command);
    }

    public function testDeleteUserWithWrongUser(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $actualUserId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';
        $command = new DeleteAUserCommand(UserId::fromString($userId));

        $user = User::create(
            UserId::fromString($actualUserId),
            UserEmail::fromString('test@example.com'),
            UserFirstname::fromString('Test'),
            UserLastname::fromString('User'),
            UserLanguagePreference::fromString('fr'),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
        );

        $registry = UserEmailRegistry::create(
            UserEmailRegistryId::fromString(UserEmailRegistry::DEFAULT_ID)
        );

        $this->eventStore->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function($id) use ($userId, $user, $registry) {
                if ($id === $userId) {
                    return $user;
                }
                if ($id === UserEmailRegistry::DEFAULT_ID) {
                    return $registry;
                }
                throw new \RuntimeException("Unexpected ID: $id");
            });

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(UserIsNotOwnedByUserException::class);
        $this->handler->__invoke($command);
    }
}
