<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeNameRegisteredDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeNameReleasedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUser;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\Identifiers\BudgetEnvelopeNameRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;
use App\SharedContext\Domain\ValueObjects\UserId;

final class BudgetEnvelopeNameRegistryBuilder
{
    private ?BudgetEnvelopeNameRegistry $currentRegistry = null;
    private ?BudgetEnvelopeNameRegistry $oldRegistry = null;
    public int $currentRegistryVersion = 0;
    public int $oldRegistryVersion = 0;

    private function __construct(
        private readonly EventSourcedRepository $eventSourcedRepository,
        private readonly UuidGenerator $uuidGenerator,
    ) {
    }

    public static function build(
        EventSourcedRepository $eventSourcedRepository,
        UuidGenerator $uuidGenerator,
    ): self {
        return new self($eventSourcedRepository, $uuidGenerator);
    }

    public function loadOrCreateRegistry(BudgetEnvelopeNameRegistryId $registryId): self
    {
        try {
            /** @var BudgetEnvelopeNameRegistry $registry */
            $registry = $this->eventSourcedRepository->get((string) $registryId);
            $this->currentRegistry = $registry;
            $this->currentRegistryVersion = $registry->aggregateVersion();
        } catch (EventsNotFoundForAggregate) {
            $this->currentRegistry = BudgetEnvelopeNameRegistry::create($registryId);
            $this->currentRegistryVersion = 0;
        }

        return $this;
    }

    public function ensureNameIsAvailable(
        BudgetEnvelopeName $name,
        UserId $userId,
        ?BudgetEnvelopeId $currentEnvelopeId = null,
    ): self {
        if (null === $this->currentRegistry) {
            $this->loadOrCreateRegistry(
                BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                    $userId,
                    $name,
                    $this->uuidGenerator,
                ),
            );

            return $this;
        }

        $isInUse = false;
        $currentOwner = null;

        foreach ($this->currentRegistry->raisedDomainEvents() as $event) {
            if ($event instanceof BudgetEnvelopeNameRegisteredDomainEvent_v1
                && $event->name === (string) $name
                && $event->userId === (string) $userId) {
                $isInUse = true;
                $currentOwner = $event->budgetEnvelopeId;
            }

            if ($event instanceof BudgetEnvelopeNameReleasedDomainEvent_v1
                && $event->name === (string) $name
                && $event->userId === (string) $userId) {
                $isInUse = false;
                $currentOwner = null;
            }
        }

        if ($isInUse && (null === $currentEnvelopeId || $currentOwner !== (string) $currentEnvelopeId)) {
            throw new BudgetEnvelopeNameAlreadyExistsForUser();
        }

        return $this;
    }

    public function registerName(
        BudgetEnvelopeName $name,
        UserId $userId,
        BudgetEnvelopeId $envelopeId,
    ): self {
        if (null === $this->currentRegistry) {
            $this->loadOrCreateRegistry(
                BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                    $userId,
                    $name,
                    $this->uuidGenerator,
                )
            );
        }

        $this->currentRegistry->registerName($name, $userId, $envelopeId);

        return $this;
    }

    public function loadOldRegistry(
        BudgetEnvelopeNameRegistryId $oldRegistryId,
    ): self {
        try {
            $oldRegistry = $this->eventSourcedRepository->get((string) $oldRegistryId);

            if (!$oldRegistry instanceof BudgetEnvelopeNameRegistry) {
                throw new \RuntimeException('Expected BudgetEnvelopeNameRegistry but got '.get_class($oldRegistry));
            }

            $this->oldRegistry = $oldRegistry;
            $this->oldRegistryVersion = $oldRegistry->aggregateVersion();
        } catch (EventsNotFoundForAggregate) {
            $this->oldRegistry = null;
            $this->oldRegistryVersion = 0;
        }

        return $this;
    }

    public function releaseName(
        BudgetEnvelopeName $name,
        UserId $userId,
        BudgetEnvelopeId $budgetEnvelopeId,
    ): self {
        if (null === $this->oldRegistry) {
            $this->loadOldRegistry(
                BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                    $userId,
                    $name,
                    $this->uuidGenerator,
                )
            );
        }

        $this->oldRegistry?->releaseName($name, $userId, $budgetEnvelopeId);

        return $this;
    }

    /**
     * @return array<BudgetEnvelopeNameRegistry>
     */
    public function getRegistryAggregates(): array
    {
        $registries = [];

        if (null !== $this->currentRegistry && count($this->currentRegistry->raisedDomainEvents()) > 0) {
            $registries[] = $this->currentRegistry;
        }

        if (null !== $this->oldRegistry && count($this->oldRegistry->raisedDomainEvents()) > 0) {
            $registries[] = $this->oldRegistry;
        }

        return $registries;
    }
}
