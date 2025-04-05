<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanWant;
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
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'budget_plan_uuid', type: 'string', length: 36)]
    private(set) string $budgetPlanUuid;

    #[ORM\Column(name: 'want_name', type: 'string', length: 35)]
    private(set) string $wantName;

    #[ORM\Column(name: 'want_amount', type: 'string', length: 13)]
    private(set) string $wantAmount;

    #[ORM\Column(name: 'category', type: 'string', length: 35)]
    private(set) string $category;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    private function __construct(
        BudgetPlanId $budgetPlanUuid,
        BudgetPlanWant $budgetPlanWant,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
    ) {
        $this->budgetPlanUuid = (string) $budgetPlanUuid;
        $this->uuid = $budgetPlanWant->getUuid();
        $this->wantName = $budgetPlanWant->getWantName();
        $this->wantAmount = $budgetPlanWant->getAmount();
        $this->category = $budgetPlanWant->getCategory();
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

    public static function fromBudgetPlanWantAddedDomainEvent(BudgetPlanWantAddedDomainEvent $event): self
    {
        return new self(
            BudgetPlanId::fromString($event->aggregateId),
            BudgetPlanWant::fromArray(
                [
                    'uuid' => $event->uuid,
                    'wantName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]
            ),
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
        );
    }

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
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

    public static function fromRepository(array $budgetPlanWantEntry): self
    {
        return new self(
            BudgetPlanId::fromString($budgetPlanWantEntry['budget_plan_uuid']),
            BudgetPlanWant::fromArray(
                [
                    'uuid' => $budgetPlanWantEntry['uuid'],
                    'wantName' => $budgetPlanWantEntry['want_name'],
                    'category' => $budgetPlanWantEntry['category'],
                    'amount' => $budgetPlanWantEntry['want_amount'],
                ],
            ),
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
            BudgetPlanWantAdjustedDomainEvent::class => $this->applyBudgetPlanWantAdjustedDomainEvent($event),
            default => throw new \RuntimeException('budgetPlan.unknownEvent'),
        };
    }

    private function applyBudgetPlanWantAdjustedDomainEvent(BudgetPlanWantAdjustedDomainEvent $event): void
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
