<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\RenameABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\RenameABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\RenameABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Infrastructure\Adapters\UuidGeneratorAdapter;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RenameABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private RenameABudgetEnvelopeCommandHandler $renameABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private UuidGeneratorInterface $uuidGenerator;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->uuidGenerator = new UuidGeneratorAdapter();
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->renameABudgetEnvelopeCommandHandler = new RenameABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->uuidGenerator,
        );
    }

    public function testRenameABudgetEnvelopeSuccess(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput('new test name');
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelopeCommand(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->any())
            ->method('load')
            ->willReturnCallback(function($id) use ($envelope) {
                if ($id === '10a33b8c-853a-4df8-8fc9-e8bb00b78da4') {
                    return $envelope;
                }
                throw new EventsNotFoundForAggregateException();
            });

        $this->eventStore->expects($this->once())
            ->method('trackAggregates');

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameABudgetEnvelopeNotFoundFailure(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput('new test name');
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelopeCommand(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willThrowException(new EventsNotFoundForAggregateException());

        $this->eventStore->expects($this->never())
            ->method('trackAggregates');

        $this->expectException(EventsNotFoundForAggregateException::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameDeletedEnvelope(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput('new test name');
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelopeCommand(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with('10a33b8c-853a-4df8-8fc9-e8bb00b78da4')
            ->willReturn($this->createDeletedEnvelope());

        $this->eventStore->expects($this->never())
            ->method('trackAggregates');

        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameABudgetEnvelopeWithWrongUser(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput('new test name');
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelopeCommand(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'), // Different user ID
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'), // Original owner
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with('10a33b8c-853a-4df8-8fc9-e8bb00b78da4')
            ->willReturn($envelope);

        $this->eventStore->expects($this->never())
            ->method('trackAggregates');

        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    private function createDeletedEnvelope(): BudgetEnvelope
    {
        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $envelope->delete(BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'));

        return $envelope;
    }
}
