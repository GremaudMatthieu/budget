<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Builders;

use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelopeNameRegistry;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeNameRegisteredDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeNameReleasedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeNameRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\UserId;

final class BudgetEnvelopeNameRegistryBuilder
{
    private ?BudgetEnvelopeNameRegistry $currentRegistry = null;
    private ?BudgetEnvelopeNameRegistry $oldRegistry = null;
    public int $currentRegistryVersion = 0;
    public int $oldRegistryVersion = 0;

    private function __construct(
        private readonly EventSourcedRepositoryInterface $eventSourcedRepository,
        private readonly UuidGeneratorInterface $uuidGenerator
    ) {
    }

    public static function build(
        EventSourcedRepositoryInterface $eventSourcedRepository,
        UuidGeneratorInterface $uuidGenerator
    ): self {
        return new self($eventSourcedRepository, $uuidGenerator);
    }

    public function loadOrCreateRegistry(BudgetEnvelopeNameRegistryId $registryId): self {
        try {
            /** @var BudgetEnvelopeNameRegistry $registry */
            $registry = $this->eventSourcedRepository->get((string) $registryId);
            $this->currentRegistry = $registry;
            $this->currentRegistryVersion = $registry->aggregateVersion();
        } catch (EventsNotFoundForAggregateException) {
            $this->currentRegistry = BudgetEnvelopeNameRegistry::create($registryId);
            $this->currentRegistryVersion = 0;
        }

        return $this;
    }

    public function ensureNameIsAvailable(
        BudgetEnvelopeName $name,
        UserId             $userId,
        ?BudgetEnvelopeId  $currentEnvelopeId = null
    ): self {
        if ($this->currentRegistry === null) {
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
            if ($event instanceof BudgetEnvelopeNameRegisteredDomainEvent_v1 &&
                $event->name === (string) $name &&
                $event->userId === (string) $userId) {
                $isInUse = true;
                $currentOwner = $event->budgetEnvelopeId;
            }

            if ($event instanceof BudgetEnvelopeNameReleasedDomainEvent_v1 &&
                $event->name === (string) $name &&
                $event->userId === (string) $userId) {
                $isInUse = false;
                $currentOwner = null;
            }
        }

        if ($isInUse && ($currentEnvelopeId === null || $currentOwner !== (string) $currentEnvelopeId)) {
            throw new BudgetEnvelopeNameAlreadyExistsForUserException();
        }

        return $this;
    }

    public function registerName(
        BudgetEnvelopeName $name,
        UserId             $userId,
        BudgetEnvelopeId   $envelopeId,
    ): self {
        if ($this->currentRegistry === null) {
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
        BudgetEnvelopeNameRegistryId $oldRegistryId
    ): self {
        try {
            $oldRegistry = $this->eventSourcedRepository->get((string) $oldRegistryId);

            if (!$oldRegistry instanceof BudgetEnvelopeNameRegistry) {
                throw new \RuntimeException(
                    'Expected BudgetEnvelopeNameRegistry but got ' . get_class($oldRegistry),
                );
            }

            $this->oldRegistry = $oldRegistry;
            $this->oldRegistryVersion = $oldRegistry->aggregateVersion();
        } catch (EventsNotFoundForAggregateException) {
            $this->oldRegistry = null;
            $this->oldRegistryVersion = 0;
        }

        return $this;
    }

    public function releaseName(
        BudgetEnvelopeName $name,
        UserId             $userId,
        BudgetEnvelopeId   $budgetEnvelopeId,
    ): self {
        if ($this->oldRegistry === null) {
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

        if ($this->currentRegistry !== null && count($this->currentRegistry->raisedDomainEvents()) > 0) {
            $registries[] = $this->currentRegistry;
        }

        if ($this->oldRegistry !== null && count($this->oldRegistry->raisedDomainEvents()) > 0) {
            $registries[] = $this->oldRegistry;
        }

        return $registries;
    }
}
