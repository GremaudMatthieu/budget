<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\DebitABudgetEnvelope;
use App\BudgetEnvelopeContext\Application\Commands\DebitABudgetEnvelopeHandler;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUser;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperation;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\HTTP\DTOs\DebitABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DebitABudgetEnvelopeHandlerTest extends TestCase
{
    private DebitABudgetEnvelopeHandler $debitABudgetEnvelopeCommandHandler;
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->debitABudgetEnvelopeCommandHandler = new DebitABudgetEnvelopeHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testDebitABudgetEnvelopeSuccess(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('50.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelope(
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
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelope(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willThrowException(new EventsNotFoundForAggregate());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(EventsNotFoundForAggregate::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeExceedsDebitLimit(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('150.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelope(
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

        $this->expectException(BudgetEnvelopeCurrentAmount::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitDeletedEnvelope(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('50.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelope(
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

        $this->expectException(InvalidBudgetEnvelopeOperation::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeWithWrongUser(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('50.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelope(
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

        $this->expectException(BudgetEnvelopeIsNotOwnedByUser::class);

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
