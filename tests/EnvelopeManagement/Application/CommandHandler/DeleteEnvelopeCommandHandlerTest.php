<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\Application\CommandHandler;

use App\EnvelopeManagement\Application\Command\DeleteEnvelopeCommand;
use App\EnvelopeManagement\Application\CommandHandler\DeleteEnvelopeCommandHandler;
use App\EnvelopeManagement\Domain\Adapter\AMQPStreamConnectionInterface;
use App\EnvelopeManagement\Domain\Event\EnvelopeCreatedEvent;
use App\EnvelopeManagement\Domain\Event\EnvelopeCreditedEvent;
use App\EnvelopeManagement\Domain\Event\EnvelopeDebitedEvent;
use App\EnvelopeManagement\Domain\Event\EnvelopeDeletedEvent;
use App\EnvelopeManagement\Domain\Event\EnvelopeNamedEvent;
use App\EnvelopeManagement\Domain\EventStore\EventStoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteEnvelopeCommandHandlerTest extends TestCase
{
    private DeleteEnvelopeCommandHandler $deleteEnvelopeCommandHandler;
    private AMQPStreamConnectionInterface&MockObject $amqpStreamConnection;
    private EventStoreInterface&MockObject $eventStore;

    protected function setUp(): void
    {
        $this->amqpStreamConnection = $this->createMock(AMQPStreamConnectionInterface::class);
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->deleteEnvelopeCommandHandler = new DeleteEnvelopeCommandHandler(
            $this->amqpStreamConnection,
            $this->eventStore,
        );
    }

    public function testDeleteEnvelopeSuccess(): void
    {
        $deleteEnvelopeCommand = new DeleteEnvelopeCommand(
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            'a871e446-ddcd-4e7a-9bf9-525bab84e566',
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            [
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeCreatedEvent::class,
                    'occured_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'name' => 'test1',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'targetBudget' => '20.00',
                    ]),
                ],
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeNamedEvent::class,
                    'occured_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'name' => 'test2',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    ]),
                ],
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeDebitedEvent::class,
                    'occured_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'debitMoney' => '12.46',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    ]),
                ],
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeCreditedEvent::class,
                    'occured_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'creditMoney' => '5.47',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    ]),
                ],
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeDeletedEvent::class,
                    'occured_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'creditMoney' => '5.47',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'isDeleted' => true,
                    ]),
                ],
            ],
        );

        $this->eventStore->expects($this->once())->method('save');
        $this->amqpStreamConnection->expects($this->once())->method('publishEvents');

        $this->deleteEnvelopeCommandHandler->__invoke($deleteEnvelopeCommand);
    }
}
