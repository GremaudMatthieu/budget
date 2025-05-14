<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\ValueObjects\Context;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_envelope_view')]
#[ORM\Index(name: 'idx_budget_envelope_view_user_uuid', columns: ['user_uuid'])]
#[ORM\Index(name: 'idx_budget_envelope_view_uuid', columns: ['uuid'])]
final class BudgetEnvelopeView implements BudgetEnvelopeViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'budget_envelope_view_id_seq', allocationSize: 1, initialValue: 1)]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    #[ORM\Column(name: 'current_amount', type: 'string', length: 13)]
    private(set) string $currentAmount;

    #[ORM\Column(name: 'targeted_amount', type: 'string', length: 13)]
    private(set) string $targetedAmount;

    #[ORM\Column(name: 'name', type: 'string', length: 25)]
    private(set) string $name;

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    private(set) string $currency;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private(set) string $userUuid;

    #[ORM\Column(name: 'context_uuid', type: 'string', length: 36)]
    private(set) string $contextUuid;

    #[ORM\Column(name: 'context', type: 'string', length: 36)]
    private(set) string $context;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private(set) bool $isDeleted;

    private function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeTargetedAmount $targetedAmount,
        BudgetEnvelopeName $name,
        BudgetEnvelopeUserId $userId,
        BudgetEnvelopeCurrentAmount $currentAmount,
        BudgetEnvelopeCurrency $currency,
        Context $context,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        bool $isDeleted,
    ) {
        $this->uuid = (string) $budgetEnvelopeId;
        $this->currentAmount = (string) $currentAmount;
        $this->targetedAmount = (string) $targetedAmount;
        $this->name = (string) $name;
        $this->userUuid = (string) $userId;
        $this->currency = (string) $currency;
        $this->isDeleted = $isDeleted;
        $this->context = $context->getContext();
        $this->contextUuid = $context->getContextId();
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    #[\Override]
    public static function fromRepository(array $budgetEnvelope): self
    {
        return new self(
            BudgetEnvelopeId::fromString($budgetEnvelope['uuid']),
            BudgetEnvelopeTargetedAmount::fromString(
                $budgetEnvelope['targeted_amount'],
                $budgetEnvelope['current_amount'],
            ),
            BudgetEnvelopeName::fromString($budgetEnvelope['name']),
            BudgetEnvelopeUserId::fromString($budgetEnvelope['user_uuid']),
            BudgetEnvelopeCurrentAmount::fromString(
                $budgetEnvelope['current_amount'],
                $budgetEnvelope['targeted_amount'],
            ),
            BudgetEnvelopeCurrency::fromString($budgetEnvelope['currency']),
            Context::from($budgetEnvelope['context_uuid'], $budgetEnvelope['context']),
            new \DateTimeImmutable($budgetEnvelope['created_at']),
            new \DateTime($budgetEnvelope['updated_at']),
            (bool) $budgetEnvelope['is_deleted'],
        )
        ;
    }

    public static function fromBudgetEnvelopeAddedDomainEvent(BudgetEnvelopeAddedDomainEvent $event): self
    {
        return new self(
            BudgetEnvelopeId::fromString($event->aggregateId),
            BudgetEnvelopeTargetedAmount::fromString(
                $event->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeName::fromString($event->name),
            BudgetEnvelopeUserId::fromString($event->userId),
            BudgetEnvelopeCurrentAmount::fromString(
                '0.00',
                $event->targetedAmount,
            ),
            BudgetEnvelopeCurrency::fromString($event->currency),
            Context::from($event->contextId, $event->context),
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
            BudgetEnvelopeAddedDomainEvent::class => $this->applyBudgetEnvelopeAddedDomainEvent($event),
            BudgetEnvelopeRenamedDomainEvent::class => $this->applyBudgetEnvelopeRenamedDomainEvent($event),
            BudgetEnvelopeCreditedDomainEvent::class => $this->applyBudgetEnvelopeCreditedDomainEvent($event),
            BudgetEnvelopeDebitedDomainEvent::class => $this->applyBudgetEnvelopeDebitedDomainEvent($event),
            BudgetEnvelopeDeletedDomainEvent::class => $this->applyBudgetEnvelopeDeletedDomainEvent($event),
            BudgetEnvelopeRewoundDomainEvent::class => $this->applyBudgetEnvelopeRewoundDomainEvent($event),
            BudgetEnvelopeReplayedDomainEvent::class => $this->applyBudgetEnvelopeReplayedDomainEvent($event),
            BudgetEnvelopeTargetedAmountChangedDomainEvent::class => $this->applyBudgetEnvelopeTargetedAmountChangedDomainEvent($event),
            BudgetEnvelopeCurrencyChangedDomainEvent::class => $this->applyBudgetEnvelopeCurrencyChangedDomainEvent($event),
            default => throw new \RuntimeException('envelopes.unknownEvent'),
        };
    }

    private function applyBudgetEnvelopeAddedDomainEvent(BudgetEnvelopeAddedDomainEvent $event): void
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

    private function applyBudgetEnvelopeRenamedDomainEvent(BudgetEnvelopeRenamedDomainEvent $event): void
    {
        $this->name = $event->name;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeCreditedDomainEvent(BudgetEnvelopeCreditedDomainEvent $event): void
    {
        $this->currentAmount = (string) (
            floatval($this->currentAmount) + floatval($event->creditMoney)
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeDebitedDomainEvent(BudgetEnvelopeDebitedDomainEvent $event): void
    {
        $this->currentAmount = (string) (
            floatval($this->currentAmount) - floatval($event->debitMoney)
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeDeletedDomainEvent(BudgetEnvelopeDeletedDomainEvent $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeRewoundDomainEvent(BudgetEnvelopeRewoundDomainEvent $event): void
    {
        $this->targetedAmount = $event->targetedAmount;
        $this->currentAmount = $event->currentAmount;
        $this->name = $event->name;
        $this->currency = $event->currency;
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyBudgetEnvelopeReplayedDomainEvent(BudgetEnvelopeReplayedDomainEvent $event): void
    {
        $this->targetedAmount = $event->targetedAmount;
        $this->currentAmount = $event->currentAmount;
        $this->name = $event->name;
        $this->currency = $event->currency;
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyBudgetEnvelopeTargetedAmountChangedDomainEvent(
        BudgetEnvelopeTargetedAmountChangedDomainEvent $event,
    ): void {
        $this->targetedAmount = $event->targetedAmount;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeCurrencyChangedDomainEvent(
        BudgetEnvelopeCurrencyChangedDomainEvent $event,
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
