<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanCurrencyChangedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanCurrency;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_view')]
#[ORM\UniqueConstraint(name: 'unique_budget_plan_for_user', columns: ['user_uuid', 'date'])]
#[ORM\Index(name: 'idx_budget_plan_view_user_uuid', columns: ['user_uuid'])]
#[ORM\Index(name: 'idx_budget_plan_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_view_date', columns: ['date'])]
final class BudgetPlanView implements \JsonSerializable, BudgetPlanViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_plan_view_id_seq', allocationSize: 1, initialValue: 1)]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private(set) string $userId;

    #[ORM\Column(name: 'date', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $date;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    private(set) string $currency;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private(set) bool $isDeleted;

    private function __construct(
        BudgetPlanId $budgetPlanId,
        BudgetPlanUserId $budgetPlanUserId,
        BudgetPlanCurrency $budgetPlanCurrency,
        \DateTimeImmutable $date,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        bool $isDeleted,
    ) {
        $this->uuid = (string) $budgetPlanId;
        $this->userId = (string) $budgetPlanUserId;
        $this->currency = (string) $budgetPlanCurrency;
        $this->date = $date;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->isDeleted = $isDeleted;
    }

    public static function fromBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): self
    {
        return new self(
            BudgetPlanId::fromString($event->aggregateId),
            BudgetPlanUserId::fromString($event->userId),
            BudgetPlanCurrency::fromString($event->currency),
            new \DateTimeImmutable($event->date),
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
            false,
        );
    }

    public static function fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): self {
        return new self(
            BudgetPlanId::fromString($event->aggregateId),
            BudgetPlanUserId::fromString($event->userId),
            BudgetPlanCurrency::fromString($event->currency),
            new \DateTimeImmutable($event->date),
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
            false,
        );
    }

    public static function fromRepository(array $budgetPlan): BudgetPlanViewInterface
    {
        return new self(
            BudgetPlanId::fromString($budgetPlan['uuid']),
            BudgetPlanUserId::fromString($budgetPlan['user_uuid']),
            BudgetPlanCurrency::fromString($budgetPlan['currency']),
            new \DateTimeImmutable($budgetPlan['date']),
            new \DateTimeImmutable($budgetPlan['created_at']),
            \DateTime::createFromImmutable(new \DateTimeImmutable($budgetPlan['updated_at'])),
            (bool) $budgetPlan['is_deleted'],
        );
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'userId' => $this->userId,
            'currency' => $this->currency,
            'date' => $this->date->format(\DateTimeInterface::ATOM),
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'userId' => $this->userId,
            'currency' => $this->currency,
            'date' => $this->date->format(\DateTimeInterface::ATOM),
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->applyBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanRemovedDomainEvent::class => $this->applyBudgetPlanRemovedDomainEvent($event),
            BudgetPlanCurrencyChangedDomainEvent::class => $this->applyBudgetPlanCurrencyChangedDomainEvent($event),
            BudgetPlanIncomeAddedDomainEvent::class => $this->applyBudgetPlanIncomeAddedDomainEvent($event),
            BudgetPlanNeedAddedDomainEvent::class => $this->applyBudgetPlanNeedAddedDomainEvent($event),
            BudgetPlanSavingAddedDomainEvent::class => $this->applyBudgetPlanSavingAddedDomainEvent($event),
            BudgetPlanWantAddedDomainEvent::class => $this->applyBudgetPlanWantAddedDomainEvent($event),
            BudgetPlanIncomeAdjustedDomainEvent::class => $this->applyBudgetPlanIncomeAdjustedDomainEvent($event),
            BudgetPlanSavingAdjustedDomainEvent::class => $this->applyBudgetPlanSavingAdjustedDomainEvent($event),
            BudgetPlanWantAdjustedDomainEvent::class => $this->applyBudgetPlanWantAdjustedDomainEvent($event),
            BudgetPlanNeedAdjustedDomainEvent::class => $this->applyBudgetPlanNeedAdjustedDomainEvent($event),
            BudgetPlanIncomeRemovedDomainEvent::class => $this->handleBudgetPlanIncomeRemovedDomainEvent($event),
            BudgetPlanWantRemovedDomainEvent::class => $this->handleBudgetPlanWantRemovedDomainEvent($event),
            BudgetPlanNeedRemovedDomainEvent::class => $this->handleBudgetPlanNeedRemovedDomainEvent($event),
            BudgetPlanSavingRemovedDomainEvent::class => $this->handleBudgetPlanSavingRemovedDomainEvent($event),
            default => throw new \RuntimeException('Unknown event type'),
        };
    }

    private function applyBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        $this->uuid = $event->aggregateId;
        $this->userId = $event->userId;
        $this->currency = $event->currency;
        $this->date = new \DateTimeImmutable($event->date);
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->isDeleted = false;
    }

    private function applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): void {
        $this->uuid = $event->aggregateId;
        $this->userId = $event->userId;
        $this->currency = $event->currency;
        $this->date = new \DateTimeImmutable($event->date);
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->isDeleted = false;
    }

    private function applyBudgetPlanCurrencyChangedDomainEvent(BudgetPlanCurrencyChangedDomainEvent $event): void
    {
        $this->currency = $event->currency;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanIncomeAddedDomainEvent(BudgetPlanIncomeAddedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanNeedAddedDomainEvent(BudgetPlanNeedAddedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanSavingAddedDomainEvent(BudgetPlanSavingAddedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanWantAddedDomainEvent(BudgetPlanWantAddedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanIncomeAdjustedDomainEvent(BudgetPlanIncomeAdjustedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanNeedAdjustedDomainEvent(BudgetPlanNeedAdjustedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanSavingAdjustedDomainEvent(BudgetPlanSavingAdjustedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanWantAdjustedDomainEvent(BudgetPlanWantAdjustedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function handleBudgetPlanIncomeRemovedDomainEvent(BudgetPlanIncomeRemovedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function handleBudgetPlanWantRemovedDomainEvent(BudgetPlanWantRemovedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function handleBudgetPlanNeedRemovedDomainEvent(BudgetPlanNeedRemovedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function handleBudgetPlanSavingRemovedDomainEvent(BudgetPlanSavingRemovedDomainEvent $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanRemovedDomainEvent(BudgetPlanRemovedDomainEvent $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }
}
