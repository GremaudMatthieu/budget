<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_need_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_need_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_need_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
final class BudgetPlanNeedEntryView implements \JsonSerializable, BudgetPlanNeedEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_plan_need_entry_view_id_seq', allocationSize: 1, initialValue: 1)]
    public private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    public private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    public private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'need_name', type: 'string', length: 35)]
    public private(set) string $needName;

    #[ORM\Column(name: 'need_amount', type: 'string', length: 13)]
    public private(set) string $needAmount;

    #[ORM\Column(name: 'category', type: 'string', length: 35)]
    public private(set) string $category;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    public private(set) \DateTime $updatedAt;

    private function __construct(
        string $budgetPlanUuid,
        array $budgetPlanNeed,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
    ) {
        $this->budgetPlanUuid = $budgetPlanUuid;
        $this->uuid = $budgetPlanNeed['uuid'];
        $this->needName = $budgetPlanNeed['needName'];
        $this->needAmount = $budgetPlanNeed['amount'];
        $this->category = $budgetPlanNeed['category'];
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
        array $need,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            $budgetPlanUuid,
            $need,
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromBudgetPlanNeedAddedDomainEvent_v1(BudgetPlanNeedAddedDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            [
                'uuid' => $event->uuid,
                'needName' => $event->name,
                'category' => $event->category,
                'amount' => $event->amount,
            ],
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
        );
    }

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        array $need,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            $budgetPlanUuid,
            $need,
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromRepository(array $budgetPlanNeedEntry): self
    {
        return new self(
            $budgetPlanNeedEntry['budget_plan_uuid'],
            [
                'uuid' => $budgetPlanNeedEntry['uuid'],
                'needName' => $budgetPlanNeedEntry['need_name'],
                'category' => $budgetPlanNeedEntry['category'],
                'amount' => $budgetPlanNeedEntry['need_amount'],
            ],
            new \DateTimeImmutable($budgetPlanNeedEntry['created_at']),
            \DateTime::createFromImmutable(new \DateTimeImmutable($budgetPlanNeedEntry['updated_at']))
        );
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetPlanNeedAdjustedDomainEvent_v1::class => $this->applyBudgetPlanNeedAdjustedDomainEvent_v1($event),
            default => throw new \RuntimeException('budgetPlan.unknownEvent'),
        };
    }

    private function applyBudgetPlanNeedAdjustedDomainEvent_v1(BudgetPlanNeedAdjustedDomainEvent_v1 $event): void
    {
        $this->needName = $event->name;
        $this->needAmount = $event->amount;
        $this->category = $event->category;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'budgetPlanUuid' => $this->budgetPlanUuid,
            'needName' => $this->needName,
            'needAmount' => $this->needAmount,
            'category' => $this->category,
            'createdAt' => $this->createdAt->format(\DateTime::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTime::ATOM),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'budgetPlanUuid' => $this->budgetPlanUuid,
            'category' => $this->category,
            'needName' => $this->needName,
            'needAmount' => $this->needAmount,
        ];
    }
}
