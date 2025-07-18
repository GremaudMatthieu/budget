<?php

namespace App\BudgetPlanContext\Domain\Aggregates;

use App\BudgetPlanContext\Domain\Events\BudgetPlanDateRegisteredDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanDateReleasedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanDateAlreadyExistsForUserException;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanDateRegistryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\DomainEventsCapabilityTrait;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetPlanDateRegistry implements AggregateRootInterface
{
    use DomainEventsCapabilityTrait;

    private string $budgetPlanDateRegistryId;
    private array $registeredDates = [];
    private int $aggregateVersion = 0;

    private function __construct()
    {
    }

    public static function create(BudgetPlanDateRegistryId $budgetPlanDateRegistryId): self
    {
        $registry = new self();
        $registry->budgetPlanDateRegistryId = (string) $budgetPlanDateRegistryId;

        return $registry;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function registerDate(
        \DateTimeImmutable $date,
        UserId $userId,
        BudgetPlanId $budgetPlanId,
    ): void {
        $dateKey = $this->generateDateKey(UtcClock::fromImmutableToString($date), (string) $userId);

        if (isset($this->registeredDates[$dateKey]) && $this->registeredDates[$dateKey] !== (string) $budgetPlanId) {
            throw new BudgetPlanDateAlreadyExistsForUserException();
        }

        $this->raiseDomainEvent(
            new BudgetPlanDateRegisteredDomainEvent_v1(
                $this->budgetPlanDateRegistryId,
                (string) $userId,
                UtcClock::fromImmutableToString($date),
                (string) $budgetPlanId,
            ),
        );
    }

    public function releaseDate(
        \DateTimeImmutable $date,
        UserId $userId,
        BudgetPlanId $budgetPlanId,
    ): void {
        $this->raiseDomainEvent(
            new BudgetPlanDateReleasedDomainEvent_v1(
                $this->budgetPlanDateRegistryId,
                (string) $userId,
                UtcClock::fromImmutableToString($date),
                (string) $budgetPlanId,
            ),
        );
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function setAggregateVersion(int $aggregateVersion): self
    {
        $this->aggregateVersion = $aggregateVersion;

        return $this;
    }

    public function getAggregateId(): string
    {
        return $this->budgetPlanDateRegistryId;
    }

    public function applyBudgetPlanDateRegisteredDomainEvent_v1(BudgetPlanDateRegisteredDomainEvent_v1 $event): void
    {
        $dateKey = $this->generateDateKey($event->date, $event->userId);
        $this->budgetPlanDateRegistryId = $event->aggregateId;
        $this->registeredDates[$dateKey] = $event->budgetPlanId;
    }

    public function applyBudgetPlanDateReleasedDomainEvent_v1(BudgetPlanDateReleasedDomainEvent_v1 $event): void
    {
        $dateKey = $this->generateDateKey($event->date, $event->userId);
        $this->budgetPlanDateRegistryId = $event->aggregateId;
        unset($this->registeredDates[$dateKey]);
    }

    private function generateDateKey(string $date, string $userId): string
    {
        return $userId . ':' . mb_strtolower($date);
    }
}
