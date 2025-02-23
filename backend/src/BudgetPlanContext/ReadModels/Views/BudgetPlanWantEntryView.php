<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanWant;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_want_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_want_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_want_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
final readonly class BudgetPlanWantEntryView implements \JsonSerializable, BudgetPlanWantEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'want_name', type: 'string', length: 35)]
    private(set) string $wantName;

    #[ORM\Column(name: 'want_amount', type: 'string', length: 13)]
    private(set) string $wantAmount;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    private function __construct(
        BudgetPlanId $budgetPlanUuid,
        BudgetPlanWant $budgetPlanWant,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt
    ) {
        $this->budgetPlanUuid = (string) $budgetPlanUuid;
        $this->uuid = $budgetPlanWant->getUuid();
        $this->wantName = $budgetPlanWant->getWantName();
        $this->wantAmount = $budgetPlanWant->getAmount();
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $want,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanUuid),
            BudgetPlanWant::fromArray($want),
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'budgetPlanUuid' => $this->budgetPlanUuid,
            'wantName' => $this->wantName,
            'wantAmount' => $this->wantAmount,
        ];
    }
}
