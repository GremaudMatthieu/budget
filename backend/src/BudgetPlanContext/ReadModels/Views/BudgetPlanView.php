<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanCurrencyChangedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_view')]
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

    #[ORM\Column(name: 'context_uuid', type: 'string', length: 36)]
    private(set) string $contextUuid;

    #[ORM\Column(name: 'context', type: 'string', length: 36)]
    private(set) string $context;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private(set) bool $isDeleted;

    private function __construct(
        string $budgetPlanId,
        string $budgetPlanUserId,
        string $budgetPlanCurrency,
        \DateTimeImmutable $date,
        string $context,
        string $contextId,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        bool $isDeleted,
    ) {
        $this->uuid = $budgetPlanId;
        $this->userId = $budgetPlanUserId;
        $this->currency = $budgetPlanCurrency;
        $this->date = $date;
        $this->context = $context;
        $this->contextUuid = $contextId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->isDeleted = $isDeleted;
    }

    public static function fromBudgetPlanGeneratedDomainEvent_v1(BudgetPlanGeneratedDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            $event->userId,
            $event->currency,
            new \DateTimeImmutable($event->date),
            $event->context,
            $event->contextId,
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
            false,
        );
    }

    public static function fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1 $event,
    ): self {
        return new self(
            $event->aggregateId,
            $event->userId,
            $event->currency,
            new \DateTimeImmutable($event->date),
            $event->context,
            $event->contextId,
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
            false,
        );
    }

    public static function fromRepository(array $budgetPlan): BudgetPlanViewInterface
    {
        return new self(
            $budgetPlan['uuid'],
            $budgetPlan['user_uuid'],
            $budgetPlan['currency'],
            new \DateTimeImmutable($budgetPlan['date']),
            $budgetPlan['context'],
            $budgetPlan['context_uuid'],
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
            BudgetPlanGeneratedDomainEvent_v1::class => $this->applyBudgetPlanGeneratedDomainEvent_v1($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1::class => $this->applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1($event),
            BudgetPlanRemovedDomainEvent_v1::class => $this->applyBudgetPlanRemovedDomainEvent_v1($event),
            BudgetPlanCurrencyChangedDomainEvent_v1::class => $this->applyBudgetPlanCurrencyChangedDomainEvent_v1($event),
            BudgetPlanIncomeAddedDomainEvent_v1::class => $this->applyBudgetPlanIncomeAddedDomainEvent_v1($event),
            BudgetPlanNeedAddedDomainEvent_v1::class => $this->applyBudgetPlanNeedAddedDomainEvent_v1($event),
            BudgetPlanSavingAddedDomainEvent_v1::class => $this->applyBudgetPlanSavingAddedDomainEvent_v1($event),
            BudgetPlanWantAddedDomainEvent_v1::class => $this->applyBudgetPlanWantAddedDomainEvent_v1($event),
            BudgetPlanIncomeAdjustedDomainEvent_v1::class => $this->applyBudgetPlanIncomeAdjustedDomainEvent_v1($event),
            BudgetPlanSavingAdjustedDomainEvent_v1::class => $this->applyBudgetPlanSavingAdjustedDomainEvent_v1($event),
            BudgetPlanWantAdjustedDomainEvent_v1::class => $this->applyBudgetPlanWantAdjustedDomainEvent_v1($event),
            BudgetPlanNeedAdjustedDomainEvent_v1::class => $this->applyBudgetPlanNeedAdjustedDomainEvent_v1($event),
            BudgetPlanIncomeRemovedDomainEvent_v1::class => $this->handleBudgetPlanIncomeRemovedDomainEvent_v1($event),
            BudgetPlanWantRemovedDomainEvent_v1::class => $this->handleBudgetPlanWantRemovedDomainEvent_v1($event),
            BudgetPlanNeedRemovedDomainEvent_v1::class => $this->handleBudgetPlanNeedRemovedDomainEvent_v1($event),
            BudgetPlanSavingRemovedDomainEvent_v1::class => $this->handleBudgetPlanSavingRemovedDomainEvent_v1($event),
            default => throw new \RuntimeException('Unknown event type'),
        };
    }

    private function applyBudgetPlanGeneratedDomainEvent_v1(BudgetPlanGeneratedDomainEvent_v1 $event): void
    {
        $this->uuid = $event->aggregateId;
        $this->userId = $event->userId;
        $this->currency = $event->currency;
        $this->date = new \DateTimeImmutable($event->date);
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->context = $event->context;
        $this->contextUuid = $event->contextId;
        $this->isDeleted = false;
    }

    private function applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1 $event,
    ): void {
        $this->uuid = $event->aggregateId;
        $this->userId = $event->userId;
        $this->currency = $event->currency;
        $this->date = new \DateTimeImmutable($event->date);
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->context = $event->context;
        $this->contextUuid = $event->contextId;
        $this->isDeleted = false;
    }

    private function applyBudgetPlanCurrencyChangedDomainEvent_v1(BudgetPlanCurrencyChangedDomainEvent_v1 $event): void
    {
        $this->currency = $event->currency;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanIncomeAddedDomainEvent_v1(BudgetPlanIncomeAddedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanNeedAddedDomainEvent_v1(BudgetPlanNeedAddedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanSavingAddedDomainEvent_v1(BudgetPlanSavingAddedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanWantAddedDomainEvent_v1(BudgetPlanWantAddedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

     private function applyBudgetPlanIncomeAdjustedDomainEvent_v1(BudgetPlanIncomeAdjustedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanNeedAdjustedDomainEvent_v1(BudgetPlanNeedAdjustedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanSavingAdjustedDomainEvent_v1(BudgetPlanSavingAdjustedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanWantAdjustedDomainEvent_v1(BudgetPlanWantAdjustedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function handleBudgetPlanIncomeRemovedDomainEvent_v1(BudgetPlanIncomeRemovedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function handleBudgetPlanWantRemovedDomainEvent_v1(BudgetPlanWantRemovedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function handleBudgetPlanNeedRemovedDomainEvent_v1(BudgetPlanNeedRemovedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function handleBudgetPlanSavingRemovedDomainEvent_v1(BudgetPlanSavingRemovedDomainEvent_v1 $event): void
    {
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetPlanRemovedDomainEvent_v1(BudgetPlanRemovedDomainEvent_v1 $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }
}
