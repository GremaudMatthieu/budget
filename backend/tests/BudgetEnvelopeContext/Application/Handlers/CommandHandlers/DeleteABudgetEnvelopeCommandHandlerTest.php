<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\DeleteABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\DeleteABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelopeNameRegistry;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeNameRegistryId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Infrastructure\Adapters\UuidGeneratorAdapter;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private DeleteABudgetEnvelopeCommandHandler $deleteABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private UuidGeneratorInterface $uuidGenerator;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->uuidGenerator = new UuidGeneratorAdapter();
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->deleteABudgetEnvelopeCommandHandler = new DeleteABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->uuidGenerator,
        );
    }

    public function testDeleteABudgetEnvelopeSuccess(): void
    {
        $userId = 'd26cc02e-99e7-428c-9d61-572dff3f84a7';
        $envelopeId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';
        $envelopeName = 'test name';

        $deleteABudgetEnvelopeCommand = new DeleteABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString($envelopeName),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(function ($id) use ($envelope, $envelopeId) {
                $nameRegistryId = 'd26cc02e-99e7-428c-9d61-572dff3f84a7';
                if ($id === $envelopeId) {
                    return $envelope;
                }
                if ($id === $nameRegistryId) {
                    throw new EventsNotFoundForAggregateException();
                }
                throw new EventsNotFoundForAggregateException();
            });

        $this->deleteABudgetEnvelopeCommandHandler->__invoke($deleteABudgetEnvelopeCommand);
    }

    public function testDeleteABudgetEnvelopeWithExistingNameRegistry(): void
    {
        $userId = 'a871e446-ddcd-4e7a-9bf9-525bab84e566';
        $envelopeId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';
        $envelopeName = 'test name';

        $deleteABudgetEnvelopeCommand = new DeleteABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString($envelopeName),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $nameRegistryId = BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
            UserId::fromString($userId),
            BudgetEnvelopeName::fromString($envelopeName),
            $this->uuidGenerator,
        );
        $registry = BudgetEnvelopeNameRegistry::create($nameRegistryId);

        $this->eventStore->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(function ($id) use ($envelope, $registry, $envelopeId, $nameRegistryId) {
                if ($id === $envelopeId) {
                    return $envelope;
                }
                if ($id === $nameRegistryId) {
                    return $registry;
                }
                throw new EventsNotFoundForAggregateException();
            });

        $this->deleteABudgetEnvelopeCommandHandler->__invoke($deleteABudgetEnvelopeCommand);
    }

    public function testDeleteABudgetEnvelopeAlreadyDeleted(): void
    {
        $userId = 'a871e446-ddcd-4e7a-9bf9-525bab84e566';
        $envelopeId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';

        $deleteABudgetEnvelopeCommand = new DeleteABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
        );

        $envelope = $this->createDeletedEnvelope();

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($envelopeId)
            ->willReturn($envelope);

        $this->eventStore->expects($this->never())->method('saveMultiAggregate');
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->deleteABudgetEnvelopeCommandHandler->__invoke($deleteABudgetEnvelopeCommand);
    }

    public function testDeleteABudgetEnvelopeWithWrongUser(): void
    {
        $ownerId = 'a871e446-ddcd-4e7a-9bf9-525bab84e566';
        $wrongUserId = '0d6851a2-5123-40df-939b-8f043850fbf1';
        $envelopeId = '10a33b8c-853a-4df8-8fc9-e8bb00b78da4';

        $deleteABudgetEnvelopeCommand = new DeleteABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($wrongUserId),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($ownerId),
            BudgetEnvelopeTargetedAmount::fromString('2000.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($envelopeId)
            ->willReturn($envelope);

        $this->eventStore->expects($this->never())->method('saveMultiAggregate');
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->deleteABudgetEnvelopeCommandHandler->__invoke($deleteABudgetEnvelopeCommand);
    }

    private function createDeletedEnvelope(): BudgetEnvelope
    {
        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $envelope->delete(UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'));

        return $envelope;
    }
}
