<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Kernel;
use App\Libraries\Anonymii\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\Libraries\FluxCapacitor\Services\EventClassMap;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserContext\Application\Commands\DeleteAUserCommand;
use App\UserContext\Application\Handlers\CommandHandlers\DeleteAUserCommandHandler;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserContext\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAUserCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventEncryptorInterface&MockObject $eventEncryptor;
    private EventSourcedRepository $eventSourcedRepository;
    private DeleteAUserCommandHandler $handler;
    private EventClassMap $eventClassMap;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->eventClassMap = new EventClassMap(new Kernel('test', false));
        $this->handler = new DeleteAUserCommandHandler(
            $this->eventSourcedRepository,
            $this->eventEncryptor,
            $this->eventClassMap,
        );
    }

    public function testDeleteUserSuccess(): void
    {
        $command = new DeleteAUserCommand(UserId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'));

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'event_name' => UserSignedUpDomainEvent::class,
                        'stream_version' => 0,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'email' => 'test@mail.com',
                            'password' => 'password',
                            'firstname' => 'Test firstName',
                            'lastname' => 'Test lastName',
                            'languagePreference' => 'fr',
                            'isConsentGiven' => true,
                            'isDeleted' => false,
                            'occurredOn' => '2024-12-07T22:03:35+00:00',
                            'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'userId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                            'roles' => ['ROLE_USER'],
                        ]),
                    ],
                ],
            ),
        );
        $this->eventStore->expects($this->once())->method('save');
        $this->eventEncryptor->expects($this->once())->method('decrypt')->willReturn(
            new UserSignedUpDomainEvent(
                '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                'test@mail.com',
                'password',
                'Test firstName',
                'Test lastName',
                'fr',
                true,
                ['ROLE_USER'],
                '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            ),
        );

        $this->handler->__invoke($command);
    }

    public function testDeleteUserWithWrongUser(): void
    {
        $command = new DeleteAUserCommand(UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'));

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'event_name' => UserSignedUpDomainEvent::class,
                        'stream_version' => 0,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'email' => 'test@mail.com',
                            'password' => 'password',
                            'firstname' => 'Test firstName',
                            'lastname' => 'Test lastName',
                            'languagePreference' => 'fr',
                            'isConsentGiven' => true,
                            'isDeleted' => false,
                            'occurredOn' => '2024-12-07T22:03:35+00:00',
                            'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'userId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                            'roles' => ['ROLE_USER'],
                        ]),
                    ],
                ],
            ),
        );
        $this->eventEncryptor->expects($this->once())->method('decrypt')->willReturn(
            new UserSignedUpDomainEvent(
                '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                'test@mail.com',
                'password',
                'Test firstName',
                'Test lastName',
                'fr',
                true,
                ['ROLE_USER'],
                '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            ),
        );
        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(UserIsNotOwnedByUserException::class);

        $this->handler->__invoke($command);
    }
}
