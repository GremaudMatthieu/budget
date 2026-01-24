<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeTargetedAmountHandler;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUser;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeTargetedAmount as BudgetEnvelopeTargetedAmountException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperation;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\HTTP\DTOs\ChangeABudgetEnvelopeTargetedAmountInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeABudgetEnvelopeTargetedAmountHandlerTest extends TestCase
{
    private ChangeABudgetEnvelopeTargetedAmountHandler $changeABudgetEnvelopeTargetedAmountCommandHandler;
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler = new ChangeABudgetEnvelopeTargetedAmountHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testChangeABudgetEnvelopeTargetedAmountSuccess(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('100.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmount(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
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

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with('10a33b8c-853a-4df8-8fc9-e8bb00b78da4')
            ->willReturn($envelope);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountNotFoundFailure(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('100.00', '50.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmount(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willThrowException(new EventsNotFoundForAggregate());

        $this->expectException(EventsNotFoundForAggregate::class);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountIsBelowCurrentAmount(): void
    {
        $this->expectException(BudgetEnvelopeTargetedAmountException::class);

        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('20.00', '30.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmount(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '30.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($envelope);

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountOnDeletedEnvelope(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('100.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmount(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($this->createDeletedEnvelope());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(InvalidBudgetEnvelopeOperation::class);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountBelowZero(): void
    {
        $this->expectException(BudgetEnvelopeTargetedAmountException::class);
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('-100.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmount(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('50.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($envelope);

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(BudgetEnvelopeTargetedAmountException::class);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountWithWrongUser(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('3000.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmount(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
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

        $this->expectException(BudgetEnvelopeIsNotOwnedByUser::class);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
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
