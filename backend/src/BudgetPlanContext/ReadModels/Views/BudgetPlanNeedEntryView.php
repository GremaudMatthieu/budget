<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanNeed;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_need_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_need_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_need_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
final readonly class BudgetPlanNeedEntryView implements \JsonSerializable, BudgetPlanNeedEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'need_name', type: 'string', length: 35)]
    private(set) string $needName;

    #[ORM\Column(name: 'need_amount', type: 'string', length: 13)]
    private(set) string $needAmount;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    private function __construct(
        BudgetPlanId $budgetPlanUuid,
        BudgetPlanNeed $budgetPlanNeed,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt
    ) {
        $this->budgetPlanUuid = (string) $budgetPlanUuid;
        $this->uuid = $budgetPlanNeed->getUuid();
        $this->needName = $budgetPlanNeed->getNeedName();
        $this->needAmount = $budgetPlanNeed->getAmount();
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $need,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanUuid),
            BudgetPlanNeed::fromArray($need),
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'budgetPlanUuid' => $this->budgetPlanUuid,
            'needName' => $this->needName,
            'needAmount' => $this->needAmount,
        ];
    }
}
