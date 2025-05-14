<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeCurrencyCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\ChangeABudgetEnvelopeCurrencyCommandHandler;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\ChangeABudgetEnvelopeCurrencyInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use Assert\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeABudgetEnvelopeCurrencyCommandHandlerTest extends TestCase
{
    private ChangeABudgetEnvelopeCurrencyCommandHandler $changeABudgetEnvelopeCurrencyCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->changeABudgetEnvelopeCurrencyCommandHandler = new ChangeABudgetEnvelopeCurrencyCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testChangeABudgetEnvelopeCurrencySuccess(): void
    {
        $changeABudgetEnvelopeCurrencyInput = new ChangeABudgetEnvelopeCurrencyInput('USD');
        $changeABudgetEnvelopeCurrencyCommand = new ChangeABudgetEnvelopeCurrencyCommand(
            BudgetEnvelopeCurrency::fromString(
                $changeABudgetEnvelopeCurrencyInput->currency,
            ),
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

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with('10a33b8c-853a-4df8-8fc9-e8bb00b78da4')
            ->willReturn($envelope);

        $this->changeABudgetEnvelopeCurrencyCommandHandler->__invoke($changeABudgetEnvelopeCurrencyCommand);
    }

    public function testChangeABudgetEnvelopeCurrencyNotFoundFailure(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $changeABudgetEnvelopeCurrencyInput = new ChangeABudgetEnvelopeCurrencyInput('AAA');
        $changeABudgetEnvelopeCurrencyCommand = new ChangeABudgetEnvelopeCurrencyCommand(
            BudgetEnvelopeCurrency::fromString(
                $changeABudgetEnvelopeCurrencyInput->currency,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willThrowException(new EventsNotFoundForAggregateException());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->changeABudgetEnvelopeCurrencyCommandHandler->__invoke($changeABudgetEnvelopeCurrencyCommand);
    }

    public function testChangeABudgetEnvelopeCurrencyOnDeletedEnvelope(): void
    {
        $changeABudgetEnvelopeCurrencyInput = new ChangeABudgetEnvelopeCurrencyInput('USD');
        $changeABudgetEnvelopeCurrencyCommand = new ChangeABudgetEnvelopeCurrencyCommand(
            BudgetEnvelopeCurrency::fromString(
                $changeABudgetEnvelopeCurrencyInput->currency,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($this->createDeletedEnvelope());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->changeABudgetEnvelopeCurrencyCommandHandler->__invoke($changeABudgetEnvelopeCurrencyCommand);
    }

    public function testChangeABudgetEnvelopeCurrencyWithWrongUser(): void
    {
        $changeABudgetEnvelopeCurrencyInput = new ChangeABudgetEnvelopeCurrencyInput('USD');
        $changeABudgetEnvelopeCurrencyCommand = new ChangeABudgetEnvelopeCurrencyCommand(
            BudgetEnvelopeCurrency::fromString(
                $changeABudgetEnvelopeCurrencyInput->currency,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($envelope);

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->changeABudgetEnvelopeCurrencyCommandHandler->__invoke($changeABudgetEnvelopeCurrencyCommand);
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
