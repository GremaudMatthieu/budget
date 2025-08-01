<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\UserContext\Application\Commands\ChangeAUserFirstnameCommand;
use App\UserContext\Application\Handlers\CommandHandlers\ChangeAUserFirstnameCommandHandler;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserRegistrationContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeAUserFirstnameCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private ChangeAUserFirstnameCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new ChangeAUserFirstnameCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testUpdateUserFirstnameSuccess(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $oldFirstname = 'David';
        $newFirstname = 'John';

        $command = new ChangeAUserFirstnameCommand(
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

        $command = new ChangeAUserFirstnameCommand(
            UserId::fromString($userId),
            UserFirstname::fromString($newFirstname),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willThrowException(new EventsNotFoundForAggregateException());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(EventsNotFoundForAggregateException::class);
        $this->handler->__invoke($command);
    }
}
