<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\RenameABudgetEnvelope;
use App\BudgetEnvelopeContext\Application\Commands\RenameABudgetEnvelopeHandler;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUser;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperation;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\HTTP\DTOs\RenameABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Infrastructure\Adapters\UuidGeneratorAdapter;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RenameABudgetEnvelopeHandlerTest extends TestCase
{
    private RenameABudgetEnvelopeHandler $renameABudgetEnvelopeCommandHandler;
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private UuidGenerator $uuidGenerator;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->uuidGenerator = new UuidGeneratorAdapter();
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->renameABudgetEnvelopeCommandHandler = new RenameABudgetEnvelopeHandler(
            $this->eventSourcedRepository,
            $this->uuidGenerator,
        );
    }

    public function testRenameABudgetEnvelopeSuccess(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput('new test name');
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelope(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->any())
            ->method('load')
            ->willReturnCallback(function ($id) use ($envelope) {
                if ('10a33b8c-853a-4df8-8fc9-e8bb00b78da4' === $id) {
                    return $envelope;
                }
                throw new EventsNotFoundForAggregate();
            });

        $this->eventStore->expects($this->once())
            ->method('trackAggregates');

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameABudgetEnvelopeNotFoundFailure(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput('new test name');
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelope(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willThrowException(new EventsNotFoundForAggregate());

        $this->eventStore->expects($this->never())
            ->method('trackAggregates');

        $this->expectException(EventsNotFoundForAggregate::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameDeletedEnvelope(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput('new test name');
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelope(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with('10a33b8c-853a-4df8-8fc9-e8bb00b78da4')
            ->willReturn($this->createDeletedEnvelope());

        $this->eventStore->expects($this->never())
            ->method('trackAggregates');

        $this->expectException(InvalidBudgetEnvelopeOperation::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameABudgetEnvelopeWithWrongUser(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput('new test name');
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelope(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'), // Different user ID
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'), // Original owner
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

        $this->expectException(BudgetEnvelopeIsNotOwnedByUser::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    private function createDeletedEnvelope(): BudgetEnvelope
    {
        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $envelope->delete(UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'));

        return $envelope;
    }
}
