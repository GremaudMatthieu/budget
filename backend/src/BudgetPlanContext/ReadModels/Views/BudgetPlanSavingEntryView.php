<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_saving_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_saving_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_saving_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
final class BudgetPlanSavingEntryView implements \JsonSerializable, BudgetPlanSavingEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_plan_saving_entry_view_id_seq', allocationSize: 1, initialValue: 1)]
    public private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    public private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    public private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'saving_name', type: 'string', length: 35)]
    public private(set) string $savingName;

    #[ORM\Column(name: 'saving_amount', type: 'string', length: 13)]
    public private(set) string $savingAmount;

    #[ORM\Column(name: 'category', type: 'string', length: 35)]
    public private(set) string $category;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    public private(set) \DateTime $updatedAt;

    private function __construct(
        string $budgetPlanUuid,
        array $budgetPlanSaving,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
    ) {
        $this->budgetPlanUuid = $budgetPlanUuid;
        $this->uuid = $budgetPlanSaving['uuid'];
        $this->savingName = $budgetPlanSaving['savingName'];
        $this->savingAmount = $budgetPlanSaving['amount'];
        $this->category = $budgetPlanSaving['category'];
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            $budgetPlanUuid,
            $saving,
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromBudgetPlanSavingAddedDomainEvent_v1(BudgetPlanSavingAddedDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            [
                'uuid' => $event->uuid,
                'savingName' => $event->name,
                'category' => $event->category,
                'amount' => $event->amount,
            ],
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
        );
    }

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            $budgetPlanUuid,
            $saving,
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromRepository(array $budgetPlanSavingEntry): self
    {
        return new self(
            $budgetPlanSavingEntry['budget_plan_uuid'],
            [
                'uuid' => $budgetPlanSavingEntry['uuid'],
                'savingName' => $budgetPlanSavingEntry['saving_name'],
                'category' => $budgetPlanSavingEntry['category'],
                'amount' => $budgetPlanSavingEntry['saving_amount'],
            ],
            new \DateTimeImmutable($budgetPlanSavingEntry['created_at']),
            \DateTime::createFromImmutable(new \DateTimeImmutable($budgetPlanSavingEntry['updated_at']))
        );
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetPlanSavingAdjustedDomainEvent_v1::class => $this->applyBudgetPlanSavingAdjustedDomainEvent_v1($event),
            default => throw new \RuntimeException('budgetPlan.unknownEvent'),
        };
    }

    private function applyBudgetPlanSavingAdjustedDomainEvent_v1(BudgetPlanSavingAdjustedDomainEvent_v1 $event): void
    {
        $this->savingName = $event->name;
        $this->savingAmount = $event->amount;
        $this->category = $event->category;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'budgetPlanUuid' => $this->budgetPlanUuid,
            'savingName' => $this->savingName,
            'savingAmount' => $this->savingAmount,
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
            'savingName' => $this->savingName,
            'savingAmount' => $this->savingAmount,
        ];
    }
}
