<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncome;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_income_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_income_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_income_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
final class BudgetPlanIncomeEntryView implements \JsonSerializable, BudgetPlanIncomeEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_plan_income_entry_view_id_seq', allocationSize: 1, initialValue: 1)]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'income_name', type: 'string', length: 35)]
    private(set) string $incomeName;

    #[ORM\Column(name: 'income_amount', type: 'string', length: 13)]
    private(set) string $incomeAmount;

    #[ORM\Column(name: 'category', type: 'string', length: 35)]
    private(set) string $category;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    private function __construct(
        BudgetPlanId $budgetPlanUuid,
        BudgetPlanIncome $budgetPlanIncome,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
    ) {
        $this->budgetPlanUuid = (string) $budgetPlanUuid;
        $this->uuid = $budgetPlanIncome->getUuid();
        $this->incomeName = $budgetPlanIncome->getIncomeName();
        $this->incomeAmount = $budgetPlanIncome->getAmount();
        $this->category = $budgetPlanIncome->getCategory();
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanUuid),
            BudgetPlanIncome::fromArray($income),
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromBudgetPlanIncomeAddedDomainEvent(BudgetPlanIncomeAddedDomainEvent $event): self
    {
        return new self(
            BudgetPlanId::fromString($event->aggregateId),
            BudgetPlanIncome::fromArray(
                [
                    'uuid' => $event->uuid,
                    'incomeName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]
            ),
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
        );
    }

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanUuid),
            BudgetPlanIncome::fromArray($income),
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromRepository(array $budgetPlanIncomeEntry): self
    {
        return new self(
            BudgetPlanId::fromString($budgetPlanIncomeEntry['budget_plan_uuid']),
            BudgetPlanIncome::fromArray(
                [
                    'uuid' => $budgetPlanIncomeEntry['uuid'],
                    'incomeName' => $budgetPlanIncomeEntry['income_name'],
                    'category' => $budgetPlanIncomeEntry['category'],
                    'amount' => $budgetPlanIncomeEntry['income_amount'],
                ],
            ),
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
            BudgetPlanIncomeAdjustedDomainEvent::class => $this->applyBudgetPlanIncomeAdjustedDomainEvent($event),
            default => throw new \RuntimeException('budgetPlan.unknownEvent'),
        };
    }

    private function applyBudgetPlanIncomeAdjustedDomainEvent(BudgetPlanIncomeAdjustedDomainEvent $event): void
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
