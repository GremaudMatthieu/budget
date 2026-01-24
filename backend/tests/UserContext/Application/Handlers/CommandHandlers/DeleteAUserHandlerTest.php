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
use App\UserContext\Application\Commands\DeleteAUser;
use App\UserContext\Application\Commands\DeleteAUserHandler;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUser;
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

class DeleteAUserHandlerTest extends TestCase
{
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private DeleteAUserHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new DeleteAUserHandler(
            $this->eventSourcedRepository
        );
    }

    public function testDeleteUserSuccess(): void
    {
        $userId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';
        $userEmail = 'test@example.com';
        $command = new DeleteAUser(UserId::fromString($userId));

        $user = User::create(
            UserId::fromString($userId),
            UserEmail::fromString($userEmail),
            UserFirstname::fromString('Test'),
            UserLastname::fromString('User'),
            UserLanguagePreference::fromString('fr'),
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
            ->willReturnCallback(function ($id) use ($userId, $user, $registry) {
                if ($id === $userId) {
                    return $user;
                }
                if (UserEmailRegistry::DEFAULT_ID === $id) {
                    return $registry;
                }
                throw new \RuntimeException("Unexpected ID: $id");
            });

        $this->handler->__invoke($command);
    }

    public function testDeleteUserNotFound(): void
    {
        $userId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';
        $command = new DeleteAUser(UserId::fromString($userId));

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willThrowException(new EventsNotFoundForAggregate());

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(EventsNotFoundForAggregate::class);
        $this->handler->__invoke($command);
    }

    public function testDeleteUserWithWrongUser(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $actualUserId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';
        $command = new DeleteAUser(UserId::fromString($userId));

        $user = User::create(
            UserId::fromString($actualUserId),
            UserEmail::fromString('test@example.com'),
            UserFirstname::fromString('Test'),
            UserLastname::fromString('User'),
            UserLanguagePreference::fromString('fr'),
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
            ->willReturnCallback(function ($id) use ($userId, $user, $registry) {
                if ($id === $userId) {
                    return $user;
                }
                if (UserEmailRegistry::DEFAULT_ID === $id) {
                    return $registry;
                }
                throw new \RuntimeException("Unexpected ID: $id");
            });

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(UserIsNotOwnedByUser::class);
        $this->handler->__invoke($command);
    }
}
