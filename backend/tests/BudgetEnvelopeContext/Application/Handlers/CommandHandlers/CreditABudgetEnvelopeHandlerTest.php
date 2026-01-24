<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\CreditABudgetEnvelope;
use App\BudgetEnvelopeContext\Application\Commands\CreditABudgetEnvelopeHandler;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUser;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperation;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Presentation\BudgetEnvelope\HTTP\DTOs\CreditABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use Assert\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditABudgetEnvelopeHandlerTest extends TestCase
{
    private CreditABudgetEnvelopeHandler $creditABudgetEnvelopeCommandHandler;
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->creditABudgetEnvelopeCommandHandler = new CreditABudgetEnvelopeHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testCreditABudgetEnvelopeSuccess(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('100.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelope(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('200.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with('10a33b8c-853a-4df8-8fc9-e8bb00b78da4')
            ->willReturn($envelope);

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditABudgetEnvelopeNotFoundFailure(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('100.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelope(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willThrowException(new EventsNotFoundForAggregate());

        $this->expectException(EventsNotFoundForAggregate::class);

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditABudgetEnvelopeWithNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('-100.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelope(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('200.00', '0.00'),
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

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditABudgetEnvelopeOnDeletedEnvelope(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('100.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelope(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->willReturn($this->createDeletedEnvelope());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(InvalidBudgetEnvelopeOperation::class);

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditABudgetEnvelopeWithWrongUser(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('100.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelope(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'),
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('200.00', '0.00'),
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

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    private function createDeletedEnvelope(): BudgetEnvelope
    {
        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
            BudgetEnvelopeTargetedAmount::fromString('200.00', '0.00'),
            BudgetEnvelopeName::fromString('test name'),
            BudgetEnvelopeCurrency::fromString('USD'),
            Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $envelope->delete(UserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'));

        return $envelope;
    }
}
