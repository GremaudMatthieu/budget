<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\AddABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelopeNameRegistry;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeAlreadyExistsException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeNameRegistryId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\AddABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\Enums\ContextEnum;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Infrastructure\Adapters\UuidGeneratorAdapter;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private AddABudgetEnvelopeCommandHandler $addABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private UuidGeneratorInterface $uuidGenerator;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->uuidGenerator = new UuidGeneratorAdapter();
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->addABudgetEnvelopeCommandHandler = new AddABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->uuidGenerator,
        );
    }

    public function testAddABudgetEnvelopeSuccess(): void
    {
        $addABudgetEnvelopeInput = new AddABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00',
            'USD',
        );
        $addABudgetEnvelopeCommand = new AddABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetedAmount::fromString(
                $addABudgetEnvelopeInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeCurrency::fromString($addABudgetEnvelopeInput->currency),
            Context::from($addABudgetEnvelopeInput->uuid, $addABudgetEnvelopeInput->context),
        );

        $this->eventStore->expects($this->any())
            ->method('load')
            ->willThrowException(new EventsNotFoundForAggregateException());

        $this->eventStore->expects($this->once())
            ->method('trackAggregates');

        $this->addABudgetEnvelopeCommandHandler->__invoke($addABudgetEnvelopeCommand);
    }

    public function testAddABudgetEnvelopeWithNameDoubloonFailure(): void
    {
        $addABudgetEnvelopeInput = new AddABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00',
            'USD',
        );
        $addABudgetEnvelopeCommand = new AddABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetedAmount::fromString(
                $addABudgetEnvelopeInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeCurrency::fromString($addABudgetEnvelopeInput->currency),
            Context::from($addABudgetEnvelopeInput->uuid, $addABudgetEnvelopeInput->context),
        );

        $nameRegistryId = 'name-registry-' . md5('test name' . 'd26cc02e-99e7-428c-9d61-572dff3f84a7');

        $this->eventStore->expects($this->any())
            ->method('load')
            ->will($this->returnCallback(function ($id) use ($nameRegistryId, $addABudgetEnvelopeInput) {
                if ($id === $nameRegistryId) {
                    return BudgetEnvelopeNameRegistry::create(
                        BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
                            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
                            $this->uuidGenerator,
                        ),
                    );
                }
                return BudgetEnvelope::create(
                    BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
                    BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
                    BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
                    BudgetEnvelopeName::fromString('test name'),
                    BudgetEnvelopeCurrency::fromString('USD'),
                    Context::from($addABudgetEnvelopeInput->uuid, $addABudgetEnvelopeInput->context),
                );
            }));

        $this->expectException(BudgetEnvelopeAlreadyExistsException::class);
        $this->expectExceptionMessage(BudgetEnvelopeAlreadyExistsException::MESSAGE);

        $this->addABudgetEnvelopeCommandHandler->__invoke($addABudgetEnvelopeCommand);
    }

    public function testAddABudgetEnvelopeWithSameUuidFailure(): void
    {
        $addABudgetEnvelopeInput = new AddABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00',
            'USD',
        );
        $addABudgetEnvelopeCommand = new AddABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetedAmount::fromString(
                $addABudgetEnvelopeInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeCurrency::fromString($addABudgetEnvelopeInput->currency),
            Context::from($addABudgetEnvelopeInput->uuid, $addABudgetEnvelopeInput->context),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($addABudgetEnvelopeInput->uuid)
            ->willReturn(
                BudgetEnvelope::create(
                    BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
                    BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
                    BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
                    BudgetEnvelopeName::fromString('test name'),
                    BudgetEnvelopeCurrency::fromString('USD'),
                    Context::from($addABudgetEnvelopeInput->uuid, $addABudgetEnvelopeInput->context),
                )
            );

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(BudgetEnvelopeAlreadyExistsException::class);
        $this->expectExceptionMessage(BudgetEnvelopeAlreadyExistsException::MESSAGE);

        $this->addABudgetEnvelopeCommandHandler->__invoke($addABudgetEnvelopeCommand);
    }
}
