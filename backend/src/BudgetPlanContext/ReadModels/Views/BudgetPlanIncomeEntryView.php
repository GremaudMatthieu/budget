<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncome;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_income_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_income_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_income_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
final readonly class BudgetPlanIncomeEntryView implements \JsonSerializable, BudgetPlanIncomeEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'income_name', type: 'string', length: 35)]
    private(set) string $incomeName;

    #[ORM\Column(name: 'income_amount', type: 'string', length: 13)]
    private(set) string $incomeAmount;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    private function __construct(
        BudgetPlanId $budgetPlanUuid,
        BudgetPlanIncome $budgetPlanIncome,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt
    ) {
        $this->budgetPlanUuid = (string) $budgetPlanUuid;
        $this->uuid = $budgetPlanIncome->getUuid();
        $this->incomeName = $budgetPlanIncome->getIncomeName();
        $this->incomeAmount = $budgetPlanIncome->getAmount();
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

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'budgetPlanUuid' => $this->budgetPlanUuid,
            'incomeName' => $this->incomeName,
            'incomeAmount' => $this->incomeAmount,
        ];
    }
}
