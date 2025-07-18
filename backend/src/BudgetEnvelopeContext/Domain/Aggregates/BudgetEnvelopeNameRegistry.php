<?php

namespace App\BudgetEnvelopeContext\Domain\Aggregates;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeNameRegisteredDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeNameReleasedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeNameRegistryId;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\DomainEventsCapabilityTrait;
use App\SharedContext\Domain\ValueObjects\UserId;

final class BudgetEnvelopeNameRegistry implements AggregateRootInterface
{
    use DomainEventsCapabilityTrait;

    private string $budgetEnvelopeNameRegistryId;
    private array $registeredNames = [];
    private int $aggregateVersion = 0;

    private function __construct()
    {
    }

    public static function create(
        BudgetEnvelopeNameRegistryId $budgetEnvelopeNameRegistryId,
    ): self
    {
        $registry = new self();
        $registry->budgetEnvelopeNameRegistryId = (string) $budgetEnvelopeNameRegistryId;

        return $registry;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function registerName(
        BudgetEnvelopeName $name,
        UserId $userId,
        BudgetEnvelopeId $envelopeId
    ): void {
        $nameKey = $this->generateNameKey((string) $name, (string) $userId);

        if (isset($this->registeredNames[$nameKey]) && $this->registeredNames[$nameKey] !== (string) $envelopeId) {
            throw new BudgetEnvelopeNameAlreadyExistsForUserException();
        }

        $this->raiseDomainEvent(
            new BudgetEnvelopeNameRegisteredDomainEvent_v1(
                $this->budgetEnvelopeNameRegistryId,
                (string) $userId,
                (string) $name,
                (string) $envelopeId,
            ),
        );
    }

    public function releaseName(
        BudgetEnvelopeName $name,
        UserId $userId,
        BudgetEnvelopeId $envelopeId,
    ): void {
        $this->raiseDomainEvent(
            new BudgetEnvelopeNameReleasedDomainEvent_v1(
                $this->budgetEnvelopeNameRegistryId,
                (string) $userId,
                (string) $name,
                (string) $envelopeId,
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
        return $this->budgetEnvelopeNameRegistryId;
    }

    public function applyBudgetEnvelopeNameRegisteredDomainEvent_v1(BudgetEnvelopeNameRegisteredDomainEvent_v1 $event): void
    {
        $nameKey = $this->generateNameKey($event->name, $event->userId);
        $this->budgetEnvelopeNameRegistryId = $event->aggregateId;
        $this->registeredNames[$nameKey] = $event->budgetEnvelopeId;
    }

    public function applyBudgetEnvelopeNameReleasedDomainEvent_v1(BudgetEnvelopeNameReleasedDomainEvent_v1 $event): void
    {
        $nameKey = $this->generateNameKey($event->name, $event->userId);
        $this->budgetEnvelopeNameRegistryId = $event->aggregateId;
        unset($this->registeredNames[$nameKey]);
    }

    private function generateNameKey(string $name, string $userId): string
    {
        return $userId . ':' . mb_strtolower($name);
    }
}
