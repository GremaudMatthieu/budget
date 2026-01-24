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
use App\UserContext\Application\Commands\ChangeAUserFirstname;
use App\UserContext\Application\Commands\ChangeAUserFirstnameHandler;
use App\UserContext\Domain\User;
use App\UserContext\Domain\UserConsent;
use App\UserContext\Domain\UserEmail;
use App\UserContext\Domain\UserFirstname;
use App\UserContext\Domain\UserLastname;
use App\UserContext\Domain\UserRegistrationContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeAUserFirstnameHandlerTest extends TestCase
{
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private ChangeAUserFirstnameHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new ChangeAUserFirstnameHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testUpdateUserFirstnameSuccess(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $oldFirstname = 'David';
        $newFirstname = 'John';

        $command = new ChangeAUserFirstname(
            UserId::fromString($userId),
            UserFirstname::fromString($newFirstname),
        );

        $user = User::create(
            UserId::fromString($userId),
            UserEmail::fromString('test@example.com'),
            UserFirstname::fromString($oldFirstname),
            UserLastname::fromString('Smith'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true),
            UserRegistrationContext::fromString('google'),
            '1234567890',
            Context::from($userId, ContextEnum::USER->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willReturn($user);

        $this->handler->__invoke($command);
    }

    public function testUpdateUserFirstnameUserNotFound(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $newFirstname = 'John';

        $command = new ChangeAUserFirstname(
            UserId::fromString($userId),
            UserFirstname::fromString($newFirstname),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willThrowException(new EventsNotFoundForAggregate());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(EventsNotFoundForAggregate::class);
        $this->handler->__invoke($command);
    }
}
