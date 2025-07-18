<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_want_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_want_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_want_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
final class BudgetPlanWantEntryView implements \JsonSerializable, BudgetPlanWantEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_plan_want_entry_view_id_seq', allocationSize: 1, initialValue: 1)]
    public private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    public private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    public private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'want_name', type: 'string', length: 35)]
    public private(set) string $wantName;

    #[ORM\Column(name: 'want_amount', type: 'string', length: 13)]
    public private(set) string $wantAmount;

    #[ORM\Column(name: 'category', type: 'string', length: 35)]
    public private(set) string $category;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    public private(set) \DateTime $updatedAt;

    private function __construct(
        string $budgetPlanUuid,
        array $budgetPlanWant,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
    ) {
        $this->budgetPlanUuid = $budgetPlanUuid;
        $this->uuid = $budgetPlanWant['uuid'];
        $this->wantName = $budgetPlanWant['wantName'];
        $this->wantAmount = $budgetPlanWant['amount'];
        $this->category = $budgetPlanWant['category'];
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
        array $want,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            $budgetPlanUuid,
            $want,
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromBudgetPlanWantAddedDomainEvent_v1(BudgetPlanWantAddedDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            [
                'uuid' => $event->uuid,
                'wantName' => $event->name,
                'category' => $event->category,
                'amount' => $event->amount,
            ],
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
        );
    }

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        array $want,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            $budgetPlanUuid,
            $want,
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromRepository(array $budgetPlanWantEntry): self
    {
        return new self(
            $budgetPlanWantEntry['budget_plan_uuid'],
            [
                'uuid' => $budgetPlanWantEntry['uuid'],
                'wantName' => $budgetPlanWantEntry['want_name'],
                'category' => $budgetPlanWantEntry['category'],
                'amount' => $budgetPlanWantEntry['want_amount'],
            ],
            new \DateTimeImmutable($budgetPlanWantEntry['created_at']),
            \DateTime::createFromImmutable(new \DateTimeImmutable($budgetPlanWantEntry['updated_at']))
        );
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetPlanWantAdjustedDomainEvent_v1::class => $this->applyBudgetPlanWantAdjustedDomainEvent_v1($event),
            default => throw new \RuntimeException('budgetPlan.unknownEvent'),
        };
    }

    private function applyBudgetPlanWantAdjustedDomainEvent_v1(BudgetPlanWantAdjustedDomainEvent_v1 $event): void
    {
        $this->wantName = $event->name;
        $this->wantAmount = $event->amount;
        $this->category = $event->category;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'budgetPlanUuid' => $this->budgetPlanUuid,
            'wantName' => $this->wantName,
            'wantAmount' => $this->wantAmount,
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
            'wantName' => $this->wantName,
            'wantAmount' => $this->wantAmount,
        ];
    }
}
