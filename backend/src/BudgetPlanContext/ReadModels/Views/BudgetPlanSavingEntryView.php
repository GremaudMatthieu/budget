<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanSaving;
use App\Libraries\FluxCapacitor\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_saving_entry_view')]
#[ORM\Index(name: 'idx_budget_plan_saving_entry_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_saving_entry_budget_plan_view_uuid', columns: ['budget_plan_uuid'])]
final class BudgetPlanSavingEntryView implements \JsonSerializable, BudgetPlanSavingEntryViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'saving_name', type: 'string', length: 35)]
    private(set) string $savingName;

    #[ORM\Column(name: 'saving_amount', type: 'string', length: 13)]
    private(set) string $savingAmount;

    #[ORM\Column(name: 'category', type: 'string', length: 35)]
    private(set) string $category;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    private function __construct(
        BudgetPlanId $budgetPlanUuid,
        BudgetPlanSaving $budgetPlanSaving,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
    ) {
        $this->budgetPlanUuid = (string) $budgetPlanUuid;
        $this->uuid = $budgetPlanSaving->getUuid();
        $this->savingName = $budgetPlanSaving->getSavingName();
        $this->savingAmount = $budgetPlanSaving->getAmount();
        $this->category = $budgetPlanSaving->getCategory();
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanUuid),
            BudgetPlanSaving::fromArray($saving),
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromBudgetPlanSavingAddedDomainEvent(
        BudgetPlanSavingAddedDomainEvent $budgetPlanSavingAddedDomainEvent,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanSavingAddedDomainEvent->aggregateId),
            BudgetPlanSaving::fromArray(
                [
                    'uuid' => $budgetPlanSavingAddedDomainEvent->uuid,
                    'savingName' => $budgetPlanSavingAddedDomainEvent->name,
                    'category' => $budgetPlanSavingAddedDomainEvent->category,
                    'amount' => $budgetPlanSavingAddedDomainEvent->amount,
                ]
            ),
            $budgetPlanSavingAddedDomainEvent->occurredOn,
            \DateTime::createFromImmutable($budgetPlanSavingAddedDomainEvent->occurredOn),
        );
    }

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanUuid),
            BudgetPlanSaving::fromArray($saving),
            $occurredOn,
            \DateTime::createFromImmutable($occurredOn),
        );
    }

    public static function fromRepository(array $budgetPlanSavingEntry): self
    {
        return new self(
            BudgetPlanId::fromString($budgetPlanSavingEntry['budget_plan_uuid']),
            BudgetPlanSaving::fromArray(
                [
                    'uuid' => $budgetPlanSavingEntry['uuid'],
                    'savingName' => $budgetPlanSavingEntry['saving_name'],
                    'category' => $budgetPlanSavingEntry['category'],
                    'amount' => $budgetPlanSavingEntry['saving_amount'],
                ],
            ),
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
            BudgetPlanSavingAdjustedDomainEvent::class => $this->applyBudgetPlanSavingAdjustedDomainEvent($event),
            default => throw new \RuntimeException('budgetPlan.unknownEvent'),
        };
    }

    private function applyBudgetPlanSavingAdjustedDomainEvent(BudgetPlanSavingAdjustedDomainEvent $event): void
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
