<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_income_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_income_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_income_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
class BudgetPlanIncomeEntryView implements \JsonSerializable, BudgetPlanIncomeEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_plan_income_entry_view_id_seq', allocationSize: 1, initialValue: 1)]
    public int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    public string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    public string $budgetPlanUuid;

    #[ORM\Column(name: 'income_name', type: 'string', length: 35)]
    public string $incomeName;

    #[ORM\Column(name: 'income_amount', type: 'string', length: 13)]
    public string $incomeAmount;

    #[ORM\Column(name: 'category', type: 'string', length: 35)]
    public string $category;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    public \DateTime $updatedAt;

    private function __construct(
        string $budgetPlanUuid,
        array $budgetPlanIncome,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
    ) {
        $this->budgetPlanUuid = $budgetPlanUuid;
        $this->uuid = $budgetPlanIncome['uuid'];
        $this->incomeName = $budgetPlanIncome['incomeName'];
        $this->incomeAmount = $budgetPlanIncome['amount'];
        $this->category = $budgetPlanIncome['category'];
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            $budgetPlanUuid,
            $income,
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromBudgetPlanIncomeAddedDomainEvent_v1(BudgetPlanIncomeAddedDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            [
                'uuid' => $event->uuid,
                'incomeName' => $event->name,
                'category' => $event->category,
                'amount' => $event->amount,
            ],
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
        );
    }

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            $budgetPlanUuid,
            $income,
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromRepository(array $budgetPlanIncomeEntry): self
    {
        return new self(
            $budgetPlanIncomeEntry['budget_plan_uuid'],
            [
                'uuid' => $budgetPlanIncomeEntry['uuid'],
                'incomeName' => $budgetPlanIncomeEntry['income_name'],
                'category' => $budgetPlanIncomeEntry['category'],
                'amount' => $budgetPlanIncomeEntry['income_amount'],
            ],
            new \DateTimeImmutable($budgetPlanIncomeEntry['created_at']),
            \DateTime::createFromImmutable(new \DateTimeImmutable($budgetPlanIncomeEntry['updated_at']))
        );
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetPlanIncomeAdjustedDomainEvent_v1::class => $this->applyBudgetPlanIncomeAdjustedDomainEvent_v1($event),
            default => throw new \RuntimeException('budgetPlan.unknownEvent'),
        };
    }

    private function applyBudgetPlanIncomeAdjustedDomainEvent_v1(BudgetPlanIncomeAdjustedDomainEvent_v1 $event): void
    {
        $this->incomeName = $event->name;
        $this->incomeAmount = $event->amount;
        $this->category = $event->category;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'budgetPlanUuid' => $this->budgetPlanUuid,
            'incomeName' => $this->incomeName,
            'incomeAmount' => $this->incomeAmount,
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
            'incomeName' => $this->incomeName,
            'incomeAmount' => $this->incomeAmount,
        ];
    }
}
