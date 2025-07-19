<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_envelope_view')]
#[ORM\Index(name: 'idx_budget_envelope_view_user_uuid', columns: ['user_uuid'])]
#[ORM\Index(name: 'idx_budget_envelope_view_uuid', columns: ['uuid'])]
class BudgetEnvelopeView implements BudgetEnvelopeViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_envelope_view_id_seq', allocationSize: 1, initialValue: 1)]
    public int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    public string $uuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    public \DateTime $updatedAt;

    #[ORM\Column(name: 'current_amount', type: 'string', length: 13)]
    public string $currentAmount;

    #[ORM\Column(name: 'targeted_amount', type: 'string', length: 13)]
    public string $targetedAmount;

    #[ORM\Column(name: 'name', type: 'string', length: 25)]
    public string $name;

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    public string $currency;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    public string $userUuid;

    #[ORM\Column(name: 'context_uuid', type: 'string', length: 36)]
    public string $contextUuid;

    #[ORM\Column(name: 'context', type: 'string', length: 36)]
    public string $context;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    public bool $isDeleted;

    private function __construct(
        string $budgetEnvelopeId,
        string $targetedAmount,
        string $name,
        string $userId,
        string $currentAmount,
        string $currency,
        string $context,
        string $contextId,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        bool $isDeleted,
    ) {
        $this->uuid = $budgetEnvelopeId;
        $this->currentAmount = $currentAmount;
        $this->targetedAmount = $targetedAmount;
        $this->name = $name;
        $this->userUuid = $userId;
        $this->currency = $currency;
        $this->isDeleted = $isDeleted;
        $this->context = $context;
        $this->contextUuid = $contextId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    #[\Override]
    public static function fromRepository(array $budgetEnvelope): self
    {
        return new self(
            $budgetEnvelope['uuid'],
            $budgetEnvelope['targeted_amount'],
            $budgetEnvelope['name'],
            $budgetEnvelope['user_uuid'],
            $budgetEnvelope['current_amount'],
            $budgetEnvelope['currency'],
            $budgetEnvelope['context'],
            $budgetEnvelope['context_uuid'],
            new \DateTimeImmutable($budgetEnvelope['created_at']),
            new \DateTime($budgetEnvelope['updated_at']),
            (bool) $budgetEnvelope['is_deleted'],
        )
        ;
    }

    public static function fromBudgetEnvelopeAddedDomainEvent_v1(BudgetEnvelopeAddedDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            $event->targetedAmount,
            $event->name,
            $event->userId,
            '0.00',
            $event->currency,
            $event->context,
            $event->contextId,
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
            false,
        );
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetEnvelopeAddedDomainEvent_v1::class => $this->applyBudgetEnvelopeAddedDomainEvent_v1($event),
            BudgetEnvelopeRenamedDomainEvent_v1::class => $this->applyBudgetEnvelopeRenamedDomainEvent_v1($event),
            BudgetEnvelopeCreditedDomainEvent_v1::class => $this->applyBudgetEnvelopeCreditedDomainEvent_v1($event),
            BudgetEnvelopeDebitedDomainEvent_v1::class => $this->applyBudgetEnvelopeDebitedDomainEvent_v1($event),
            BudgetEnvelopeDeletedDomainEvent_v1::class => $this->applyBudgetEnvelopeDeletedDomainEvent_v1($event),
            BudgetEnvelopeRewoundDomainEvent_v1::class => $this->applyBudgetEnvelopeRewoundDomainEvent_v1($event),
            BudgetEnvelopeReplayedDomainEvent_v1::class => $this->applyBudgetEnvelopeReplayedDomainEvent_v1($event),
            BudgetEnvelopeTargetedAmountChangedDomainEvent_v1::class => $this->applyBudgetEnvelopeTargetedAmountChangedDomainEvent_v1($event),
            BudgetEnvelopeCurrencyChangedDomainEvent_v1::class => $this->applyBudgetEnvelopeCurrencyChangedDomainEvent_v1($event),
            default => throw new \RuntimeException('envelopes.unknownEvent'),
        };
    }

    private function applyBudgetEnvelopeAddedDomainEvent_v1(BudgetEnvelopeAddedDomainEvent_v1 $event): void
    {
        $this->uuid = $event->aggregateId;
        $this->userUuid = $event->userId;
        $this->name = $event->name;
        $this->targetedAmount = $event->targetedAmount;
        $this->currentAmount = '0.00';
        $this->currency = $event->currency;
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->context = $event->context;
        $this->contextUuid = $event->contextId;
        $this->isDeleted = false;
    }

    private function applyBudgetEnvelopeRenamedDomainEvent_v1(BudgetEnvelopeRenamedDomainEvent_v1 $event): void
    {
        $this->name = $event->name;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeCreditedDomainEvent_v1(BudgetEnvelopeCreditedDomainEvent_v1 $event): void
    {
        $this->currentAmount = (string) (
            floatval($this->currentAmount) + floatval($event->creditMoney)
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeDebitedDomainEvent_v1(BudgetEnvelopeDebitedDomainEvent_v1 $event): void
    {
        $this->currentAmount = (string) (
            floatval($this->currentAmount) - floatval($event->debitMoney)
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeDeletedDomainEvent_v1(BudgetEnvelopeDeletedDomainEvent_v1 $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeRewoundDomainEvent_v1(BudgetEnvelopeRewoundDomainEvent_v1 $event): void
    {
        $this->targetedAmount = $event->targetedAmount;
        $this->currentAmount = $event->currentAmount;
        $this->name = $event->name;
        $this->currency = $event->currency;
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyBudgetEnvelopeReplayedDomainEvent_v1(BudgetEnvelopeReplayedDomainEvent_v1 $event): void
    {
        $this->targetedAmount = $event->targetedAmount;
        $this->currentAmount = $event->currentAmount;
        $this->name = $event->name;
        $this->currency = $event->currency;
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyBudgetEnvelopeTargetedAmountChangedDomainEvent_v1(
        BudgetEnvelopeTargetedAmountChangedDomainEvent_v1 $event,
    ): void {
        $this->targetedAmount = $event->targetedAmount;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeCurrencyChangedDomainEvent_v1(
        BudgetEnvelopeCurrencyChangedDomainEvent_v1 $event,
    ): void {
        $this->currency = $event->currency;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'currentAmount' => $this->currentAmount,
            'targetedAmount' => $this->targetedAmount,
            'name' => $this->name,
            'currency' => $this->currency,
        ];
    }
}
