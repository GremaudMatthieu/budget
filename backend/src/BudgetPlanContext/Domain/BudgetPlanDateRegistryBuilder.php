<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain;

use App\BudgetPlanContext\Domain\Events\BudgetPlanDateRegisteredDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanDateReleasedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanDateAlreadyExistsForUser;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanDateRegistryId;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanId;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetPlanDateRegistryBuilder
{
    private ?BudgetPlanDateRegistry $currentRegistry = null;
    private ?BudgetPlanDateRegistry $oldRegistry = null;
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

    public function loadOrCreateRegistry(BudgetPlanDateRegistryId $registryId): self
    {
        try {
            /** @var BudgetPlanDateRegistry $registry */
            $registry = $this->eventSourcedRepository->get((string) $registryId);
            $this->currentRegistry = $registry;
            $this->currentRegistryVersion = $registry->aggregateVersion();
        } catch (EventsNotFoundForAggregate) {
            $this->currentRegistry = BudgetPlanDateRegistry::create($registryId);
            $this->currentRegistryVersion = 0;
        }

        return $this;
    }

    public function ensureDateIsAvailable(
        \DateTimeImmutable $date,
        UserId $userId,
        ?BudgetPlanId $currentPlanId = null,
    ): self {
        if (null === $this->currentRegistry) {
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
            if ($event instanceof BudgetPlanDateRegisteredDomainEvent_v1
                && $event->date === UtcClock::fromImmutableToString($date)
                && $event->userId === (string) $userId) {
                $isInUse = true;
                $currentOwner = $event->budgetPlanId;
            }

            if ($event instanceof BudgetPlanDateReleasedDomainEvent_v1
                && $event->date === UtcClock::fromImmutableToString($date)
                && $event->userId === (string) $userId) {
                $isInUse = false;
                $currentOwner = null;
            }
        }

        if ($isInUse && (null === $currentPlanId || $currentOwner !== (string) $currentPlanId)) {
            throw new BudgetPlanDateAlreadyExistsForUser();
        }

        return $this;
    }

    public function registerDate(
        \DateTimeImmutable $date,
        UserId $userId,
        BudgetPlanId $budgetPlanId,
    ): self {
        if (null === $this->currentRegistry) {
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
        BudgetPlanDateRegistryId $oldRegistryId,
    ): self {
        try {
            $oldRegistry = $this->eventSourcedRepository->get((string) $oldRegistryId);

            if (!$oldRegistry instanceof BudgetPlanDateRegistry) {
                throw new \RuntimeException('Expected BudgetPlanDateRegistry but got '.get_class($oldRegistry));
            }

            $this->oldRegistry = $oldRegistry;
            $this->oldRegistryVersion = $oldRegistry->aggregateVersion();
        } catch (EventsNotFoundForAggregate) {
            $this->oldRegistry = null;
            $this->oldRegistryVersion = 0;
        }

        return $this;
    }

    public function releaseDate(
        \DateTimeImmutable $date,
        UserId $userId,
        BudgetPlanId $budgetPlanId,
    ): self {
        if (null === $this->oldRegistry) {
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

        if (null !== $this->currentRegistry && count($this->currentRegistry->raisedDomainEvents()) > 0) {
            $registries[] = $this->currentRegistry;
        }

        if (null !== $this->oldRegistry && count($this->oldRegistry->raisedDomainEvents()) > 0) {
            $registries[] = $this->oldRegistry;
        }

        return $registries;
    }
}
