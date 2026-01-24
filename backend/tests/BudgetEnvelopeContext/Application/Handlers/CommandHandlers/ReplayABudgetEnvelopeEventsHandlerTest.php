<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ReplayABudgetEnvelopeEvents;
use App\BudgetEnvelopeContext\Application\Commands\ReplayABudgetEnvelopeEventsHandler;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReplayABudgetEnvelopeEventsHandlerTest extends TestCase
{
    private ReplayABudgetEnvelopeEventsHandler $replayABudgetEnvelopeEventsCommandHandler;
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->replayABudgetEnvelopeEventsCommandHandler = new ReplayABudgetEnvelopeEventsHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testReplayEventsSuccess(): void
    {
        $replayABudgetEnvelopeEventsCommand = new ReplayABudgetEnvelopeEvents(
            BudgetEnvelopeId::fromString('3e6a6763-4c4d-4648-bc3f-e9447dbed12c'),
            UserId::fromString('18e04f53-0ea6-478c-a02b-81b7f3d6e8c1')
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            BudgetEnvelope::create(
                BudgetEnvelopeId::fromString('3e6a6763-4c4d-4648-bc3f-e9447dbed12c'),
                UserId::fromString('18e04f53-0ea6-478c-a02b-81b7f3d6e8c1'),
                BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
                BudgetEnvelopeName::fromString('test name'),
                BudgetEnvelopeCurrency::fromString('EUR'),
                Context::from('10a33b8c-853a-4df8-8fc9-e8bb00b78da4', ContextEnum::BUDGET_ENVELOPE->value),
            ),
        );

        $this->replayABudgetEnvelopeEventsCommandHandler->__invoke($replayABudgetEnvelopeEventsCommand);
    }

    public function testReplayEventsFailure(): void
    {
        $replayABudgetEnvelopeEventsCommand = new ReplayABudgetEnvelopeEvents(
            BudgetEnvelopeId::fromString('3e6a6763-4c4d-4648-bc3f-e9447dbed12c'),
            UserId::fromString('18e04f53-0ea6-478c-a02b-81b7f3d6e8c1'),
        );

        $this->eventStore->expects($this->once())->method('load')->willThrowException(new \Exception('Error loading events'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error loading events');

        $this->replayABudgetEnvelopeEventsCommandHandler->__invoke($replayABudgetEnvelopeEventsCommand);
    }
}
