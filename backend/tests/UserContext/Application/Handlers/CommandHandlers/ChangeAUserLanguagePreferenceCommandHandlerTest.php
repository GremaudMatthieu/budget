<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\UserContext\Application\Commands\ChangeAUserLanguagePreferenceCommand;
use App\UserContext\Application\Handlers\CommandHandlers\ChangeAUserLanguagePreferenceCommandHandler;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeAUserLanguagePreferenceCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private ChangeAUserLanguagePreferenceCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new ChangeAUserLanguagePreferenceCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testUpdateUserLanguagePreferenceSuccess(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $oldLanguagePreference = 'en';
        $newLanguagePreference = 'fr';

        $command = new ChangeAUserLanguagePreferenceCommand(
            UserId::fromString($userId),
            UserLanguagePreference::fromString($newLanguagePreference),
        );

        $user = User::create(
            UserId::fromString($userId),
            UserEmail::fromString('test@example.com'),
            UserPassword::fromString('password123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Smith'),
            UserLanguagePreference::fromString($oldLanguagePreference),
            UserConsent::fromBool(true)
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willReturn($user);

        $this->handler->__invoke($command);
    }

    public function testUpdateUserLanguagePreferenceUserNotFound(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $newLanguagePreference = 'fr';

        $command = new ChangeAUserLanguagePreferenceCommand(
            UserId::fromString($userId),
            UserLanguagePreference::fromString($newLanguagePreference),
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
