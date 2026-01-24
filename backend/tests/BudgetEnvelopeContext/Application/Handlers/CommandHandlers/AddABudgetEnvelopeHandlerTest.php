<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelope;
use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelopeHandler;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeNameRegistry;
use App\BudgetEnvelopeContext\Domain\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeAlreadyExists;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeNameRegistryId;
use App\Presentation\BudgetEnvelope\HTTP\DTOs\AddABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Infrastructure\Adapters\UuidGeneratorAdapter;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddABudgetEnvelopeHandlerTest extends TestCase
{
    private AddABudgetEnvelopeHandler $addABudgetEnvelopeCommandHandler;
    private EventStore&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private UuidGenerator $uuidGenerator;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->uuidGenerator = new UuidGeneratorAdapter();
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->addABudgetEnvelopeCommandHandler = new AddABudgetEnvelopeHandler(
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
        $addABudgetEnvelopeCommand = new AddABudgetEnvelope(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
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
            ->willThrowException(new EventsNotFoundForAggregate());

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
        $addABudgetEnvelopeCommand = new AddABudgetEnvelope(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetedAmount::fromString(
                $addABudgetEnvelopeInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeCurrency::fromString($addABudgetEnvelopeInput->currency),
            Context::from($addABudgetEnvelopeInput->uuid, $addABudgetEnvelopeInput->context),
        );

        $nameRegistryId = 'name-registry-'.md5('test named26cc02e-99e7-428c-9d61-572dff3f84a7');

        $this->eventStore->expects($this->any())
            ->method('load')
            ->will($this->returnCallback(function ($id) use ($nameRegistryId, $addABudgetEnvelopeInput) {
                if ($id === $nameRegistryId) {
                    return BudgetEnvelopeNameRegistry::create(
                        BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                            UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
                            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
                            $this->uuidGenerator,
                        ),
                    );
                }

                return BudgetEnvelope::create(
                    BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
                    UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
                    BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
                    BudgetEnvelopeName::fromString('test name'),
                    BudgetEnvelopeCurrency::fromString('USD'),
                    Context::from($addABudgetEnvelopeInput->uuid, $addABudgetEnvelopeInput->context),
                );
            }));

        $this->expectException(BudgetEnvelopeAlreadyExists::class);
        $this->expectExceptionMessage(BudgetEnvelopeAlreadyExists::MESSAGE);

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
        $addABudgetEnvelopeCommand = new AddABudgetEnvelope(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
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
                    UserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
                    BudgetEnvelopeTargetedAmount::fromString('20.00', '0.00'),
                    BudgetEnvelopeName::fromString('test name'),
                    BudgetEnvelopeCurrency::fromString('USD'),
                    Context::from($addABudgetEnvelopeInput->uuid, $addABudgetEnvelopeInput->context),
                )
            );

        $this->eventStore->expects($this->never())
            ->method('saveMultiAggregate');

        $this->expectException(BudgetEnvelopeAlreadyExists::class);
        $this->expectExceptionMessage(BudgetEnvelopeAlreadyExists::MESSAGE);

        $this->addABudgetEnvelopeCommandHandler->__invoke($addABudgetEnvelopeCommand);
    }
}
