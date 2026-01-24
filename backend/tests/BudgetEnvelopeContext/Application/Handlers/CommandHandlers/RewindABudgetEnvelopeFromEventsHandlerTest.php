<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\RewindABudgetEnvelopeFromEvents;
use App\BudgetEnvelopeContext\Application\Commands\RewindABudgetEnvelopeFromEventsHandler;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeNameRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Infrastructure\Adapters\UuidGeneratorAdapter;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RewindABudgetEnvelopeFromEventsHandlerTest extends TestCase
{
    private RewindABudgetEnvelopeFromEventsHandler $rewindABudgetEnvelopeFromEventsCommandHandler;
    private EventStore&MockObject $eventStore;
    private UuidGeneratorAdapter $uuidGenerator;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->uuidGenerator = new UuidGeneratorAdapter();
        $this->rewindABudgetEnvelopeFromEventsCommandHandler = new RewindABudgetEnvelopeFromEventsHandler(
            new EventSourcedRepository($this->eventStore),
            $this->uuidGenerator,
        );
    }

    public function testRewindSuccessWithNameChange(): void
    {
        $userId = '18e04f53-0ea6-478c-a02b-81b7f3d6e8c1';
        $envelopeId = '3e6a6763-4c4d-4648-bc3f-e9447dbed12c';
        $oldName = 'old name';
        $newName = 'new name';
        $desiredDateTime = new \DateTimeImmutable('2020-10-10T12:00:00Z');

        $rewindCommand = new RewindABudgetEnvelopeFromEvents(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
            $desiredDateTime,
        );

        $oldEnvelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString($oldName),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('3e6a6763-4c4d-4648-bc3f-e9447dbed12c', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $newEnvelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString($newName),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('3e6a6763-4c4d-4648-bc3f-e9447dbed12c', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $oldNameRegistryId = BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
            UserId::fromString($userId),
            BudgetEnvelopeName::fromString($oldName),
            $this->uuidGenerator
        );

        $newNameRegistryId = BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
            UserId::fromString($userId),
            BudgetEnvelopeName::fromString($newName),
            $this->uuidGenerator
        );

        $this->eventStore->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(function ($id, $datetime = null) use ($oldEnvelope, $newEnvelope, $envelopeId) {
                if ($id === $envelopeId) {
                    return null !== $datetime ? $newEnvelope : $oldEnvelope;
                }
                throw new EventsNotFoundForAggregate();
            });

        $this->eventStore->expects($this->once())
            ->method('trackAggregates')
            ->with($this->callback(fn ($aggregates) => is_array($aggregates) && count($aggregates) >= 1));

        $this->rewindABudgetEnvelopeFromEventsCommandHandler->__invoke($rewindCommand);
    }

    public function testRewindSuccessWithNoNameChange(): void
    {
        $userId = '18e04f53-0ea6-478c-a02b-81b7f3d6e8c1';
        $envelopeId = '3e6a6763-4c4d-4648-bc3f-e9447dbed12c';
        $envelopeName = 'test name';
        $desiredDateTime = new \DateTimeImmutable('2020-10-10T12:00:00Z');

        $rewindCommand = new RewindABudgetEnvelopeFromEvents(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
            $desiredDateTime,
        );

        $envelope = BudgetEnvelope::create(
            BudgetEnvelopeId::fromString($envelopeId),
            UserId::fromString($userId),
            BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
            BudgetEnvelopeName::fromString($envelopeName),
            BudgetEnvelopeCurrency::fromString('EUR'),
            Context::from('3e6a6763-4c4d-4648-bc3f-e9447dbed12c', ContextEnum::BUDGET_ENVELOPE->value),
        );

        $nameRegistryId = BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
            UserId::fromString($userId),
            BudgetEnvelopeName::fromString($envelopeName),
            $this->uuidGenerator
        );

        $this->eventStore->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(function ($id) use ($envelope, $envelopeId) {
                if ($id === $envelopeId) {
                    return $envelope;
                }
                throw new EventsNotFoundForAggregate();
            });

        $this->eventStore->expects($this->never())
            ->method('trackAggregates');

        $this->rewindABudgetEnvelopeFromEventsCommandHandler->__invoke($rewindCommand);
    }

    public function testRewindFailure(): void
    {
        $rewindABudgetEnvelopeFromEventsCommand = new RewindABudgetEnvelopeFromEvents(
            BudgetEnvelopeId::fromString('3e6a6763-4c4d-4648-bc3f-e9447dbed12c'),
            UserId::fromString('18e04f53-0ea6-478c-a02b-81b7f3d6e8c1'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00')
        );

        $this->eventStore->expects($this->once())->method('load')->willThrowException(new \Exception('Error loading events'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error loading events');

        $this->rewindABudgetEnvelopeFromEventsCommandHandler->__invoke($rewindABudgetEnvelopeFromEventsCommand);
    }
}
