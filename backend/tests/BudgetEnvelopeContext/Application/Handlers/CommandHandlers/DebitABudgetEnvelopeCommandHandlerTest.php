<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\DebitABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\DebitABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeCurrentAmountException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\DebitABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DebitABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private DebitABudgetEnvelopeCommandHandler $debitABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->debitABudgetEnvelopeCommandHandler = new DebitABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testDebitABudgetEnvelopeSuccess(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('50.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('200.00', '100.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );
        $envelope->credit(
            BudgetEnvelopeCreditMoney::fromString('100.00'),
            BudgetEnvelopeEntryDescription::fromString('test'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($envelope);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeNotFoundFailure(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('50.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willThrowException(new EventsNotFoundForAggregateException());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(EventsNotFoundForAggregateException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeExceedsDebitLimit(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('150.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('200.00', '100.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($envelope);

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(BudgetEnvelopeCurrentAmountException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitDeletedEnvelope(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('50.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($this->createDeletedEnvelope());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeWithWrongUser(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('50.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('200.00', '100.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($envelope);
        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    private function createDeletedEnvelope(): BudgetEnvelope
    {
        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('200.00', '100.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );
        $envelope->delete(UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'));

        return $envelope;
    }
}
