<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\Application\CommandHandler;

use App\EnvelopeManagement\Application\Command\CreateEnvelopeCommand;
use App\EnvelopeManagement\Application\CommandHandler\CreateEnvelopeCommandHandler;
use App\EnvelopeManagement\Application\Dto\CreateEnvelopeInput;
use App\EnvelopeManagement\Domain\Adapter\AMQPStreamConnectionInterface;
use App\EnvelopeManagement\Domain\EventStore\EventStoreInterface;
use App\EnvelopeManagement\Domain\Exception\EnvelopeNameAlreadyExistsForUserException;
use App\EnvelopeManagement\Domain\Exception\TargetBudgetException;
use App\EnvelopeManagement\Domain\Repository\EnvelopeQueryRepositoryInterface;
use App\EnvelopeManagement\Domain\View\Envelope;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateEnvelopeCommandHandlerTest extends TestCase
{
    private CreateEnvelopeCommandHandler $createEnvelopeCommandHandler;
    private AMQPStreamConnectionInterface&MockObject $amqpStreamConnection;
    private EventStoreInterface&MockObject $eventStore;
    private EnvelopeQueryRepositoryInterface&MockObject $envelopeQueryRepository;

    protected function setUp(): void
    {
        $this->amqpStreamConnection = $this->createMock(AMQPStreamConnectionInterface::class);
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->envelopeQueryRepository = $this->createMock(EnvelopeQueryRepositoryInterface::class);

        $this->createEnvelopeCommandHandler = new CreateEnvelopeCommandHandler(
            $this->amqpStreamConnection,
            $this->eventStore,
            $this->envelopeQueryRepository,
        );
    }

    public function testCreateEnvelopeSuccess(): void
    {
        $createEnvelopeInput = new CreateEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00'
        );
        $createEnvelopeCommand = new CreateEnvelopeCommand(
            $createEnvelopeInput->getUuid(),
            'd26cc02e-99e7-428c-9d61-572dff3f84a7',
            $createEnvelopeInput->getName(),
            $createEnvelopeInput->getTargetBudget(),
        );

        $this->eventStore->expects($this->once())->method('save');
        $this->amqpStreamConnection->expects($this->once())->method('publishEvents');

        $this->createEnvelopeCommandHandler->__invoke($createEnvelopeCommand);
    }

    public function testCreateEnvelopeWithNegativeTargetBudget(): void
    {
        $createEnvelopeInput = new CreateEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '-200.00'
        );
        $createEnvelopeCommand = new CreateEnvelopeCommand(
            $createEnvelopeInput->getUuid(),
            'd26cc02e-99e7-428c-9d61-572dff3f84a7',
            $createEnvelopeInput->getName(),
            $createEnvelopeInput->getTargetBudget(),
        );

        $this->eventStore->expects($this->never())->method('save');
        $this->amqpStreamConnection->expects($this->never())->method('publishEvents');

        $this->expectException(TargetBudgetException::class);
        $this->expectExceptionMessage('Target budget must be greater than 0.');

        $this->createEnvelopeCommandHandler->__invoke($createEnvelopeCommand);
    }

    public function testCreateEnvelopeWithNameDoubloon(): void
    {
        $createEnvelopeInput = new CreateEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '-200.00'
        );
        $createEnvelopeCommand = new CreateEnvelopeCommand(
            $createEnvelopeInput->getUuid(),
            'd26cc02e-99e7-428c-9d61-572dff3f84a7',
            $createEnvelopeInput->getName(),
            $createEnvelopeInput->getTargetBudget(),
        );

        $envelopeView = Envelope::create(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'another envelope name',
                'target_budget' => '300.00',
                'current_budget' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'is_deleted' => false,
            ]
        );

        $this->envelopeQueryRepository->expects($this->once())->method('findOneBy')->willReturn($envelopeView);
        $this->eventStore->expects($this->never())->method('save');
        $this->amqpStreamConnection->expects($this->never())->method('publishEvents');

        $this->expectException(EnvelopeNameAlreadyExistsForUserException::class);
        $this->expectExceptionMessage(EnvelopeNameAlreadyExistsForUserException::MESSAGE);

        $this->createEnvelopeCommandHandler->__invoke($createEnvelopeCommand);
    }
}