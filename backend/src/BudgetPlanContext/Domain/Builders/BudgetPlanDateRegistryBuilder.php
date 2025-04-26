<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Builders;

use App\BudgetPlanContext\Domain\Aggregates\BudgetPlanDateRegistry;
use App\BudgetPlanContext\Domain\Events\BudgetPlanDateRegisteredDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanDateReleasedDomainEvent;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanDateAlreadyExistsForUserException;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanDateRegistryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetPlanDateRegistryBuilder
{
    private ?BudgetPlanDateRegistry $currentRegistry = null;
    private ?BudgetPlanDateRegistry $oldRegistry = null;
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

    public function loadOrCreateRegistry(BudgetPlanDateRegistryId $registryId): self {
        try {
            /** @var BudgetPlanDateRegistry $registry */
            $registry = $this->eventSourcedRepository->get((string) $registryId);
            $this->currentRegistry = $registry;
            $this->currentRegistryVersion = $registry->aggregateVersion();
        } catch (EventsNotFoundForAggregateException) {
            $this->currentRegistry = BudgetPlanDateRegistry::create($registryId);
            $this->currentRegistryVersion = 0;
        }

        return $this;
    }

    public function ensureDateIsAvailable(
        \DateTimeImmutable $date,
        BudgetPlanUserId $userId,
        ?BudgetPlanId $currentPlanId = null
    ): self {
        if ($this->currentRegistry === null) {
            $this->loadOrCreateRegistry(
                BudgetPlanDateRegistryId::fromUserIdAndBudgetPlanDate(
                    $userId,
                    $date,
                    $this->uuidGenerator,
                ),
            );

            return $this;
        }

        $isInUse = false;
        $currentOwner = null;

        foreach ($this->currentRegistry->raisedDomainEvents() as $event) {
            if ($event instanceof BudgetPlanDateRegisteredDomainEvent &&
                $event->date === UtcClock::fromImmutableToString($date) &&
                $event->userId === (string) $userId) {
                $isInUse = true;
                $currentOwner = $event->budgetPlanId;
            }

            if ($event instanceof BudgetPlanDateReleasedDomainEvent &&
                $event->date === UtcClock::fromImmutableToString($date) &&
                $event->userId === (string) $userId) {
                $isInUse = false;
                $currentOwner = null;
            }
        }

        if ($isInUse && ($currentPlanId === null || $currentOwner !== (string) $currentPlanId)) {
            throw new BudgetPlanDateAlreadyExistsForUserException();
        }

        return $this;
    }

    public function registerDate(
        \DateTimeImmutable $date,
        BudgetPlanUserId $userId,
        BudgetPlanId $budgetPlanId,
    ): self {
        if ($this->currentRegistry === null) {
            $this->loadOrCreateRegistry(
                BudgetPlanDateRegistryId::fromUserIdAndBudgetPlanDate(
                    $userId,
                    $date,
                    $this->uuidGenerator,
                )
            );
        }

        $this->currentRegistry->registerDate($date, $userId, $budgetPlanId);

        return $this;
    }

    public function loadOldRegistry(
        BudgetPlanDateRegistryId $oldRegistryId
    ): self {
        try {
            $oldRegistry = $this->eventSourcedRepository->get((string) $oldRegistryId);

            if (!$oldRegistry instanceof BudgetPlanDateRegistry) {
                throw new \RuntimeException(
                    'Expected BudgetPlanDateRegistry but got ' . get_class($oldRegistry),
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

    public function releaseDate(
        \DateTimeImmutable $date,
        BudgetPlanUserId $userId,
        BudgetPlanId $budgetPlanId,
    ): self {
        if ($this->oldRegistry === null) {
            $this->loadOldRegistry(
                BudgetPlanDateRegistryId::fromUserIdAndBudgetPlanDate(
                    $userId,
                    $date,
                    $this->uuidGenerator,
                )
            );
        }

        $this->oldRegistry?->releaseDate($date, $userId, $budgetPlanId);

        return $this;
    }

    /**
     * @return array<BudgetPlanDateRegistry>
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
