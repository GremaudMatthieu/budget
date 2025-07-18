<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Aggregates;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent_v1;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\SnapshotableAggregateInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\DomainEventsCapabilityTrait;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetEnvelope implements AggregateRootInterface, SnapshotableAggregateInterface
{
    use DomainEventsCapabilityTrait;

    private BudgetEnvelopeId $budgetEnvelopeId;
    private UserId $userId;
    private BudgetEnvelopeCurrentAmount $budgetEnvelopeCurrentAmount;
    private BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount;
    private BudgetEnvelopeName $budgetEnvelopeName;
    private BudgetEnvelopeCurrency $budgetEnvelopeCurrency;
    private Context $context;
    private \DateTime $updatedAt;
    private int $aggregateVersion = 0;
    private bool $isDeleted = false;

    private function __construct()
    {
    }

    public static function create(
        BudgetEnvelopeId $budgetEnvelopeId,
        UserId $budgetEnvelopeUserId,
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeName $budgetEnvelopeName,
        BudgetEnvelopeCurrency $budgetEnvelopeCurrency,
        Context $context,
    ): self {
        $aggregate = new self();
        $aggregate->raiseDomainEvent(
            new BudgetEnvelopeAddedDomainEvent_v1(
                (string) $budgetEnvelopeId,
                (string) $budgetEnvelopeUserId,
                (string) $budgetEnvelopeName,
                (string) $budgetEnvelopeTargetedAmount,
                (string) $budgetEnvelopeCurrency,
                $context->getContextId(),
                $context->getContext(),
            ),
        );

        return $aggregate;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function rename(BudgetEnvelopeName $budgetEnvelopeName, UserId $budgetEnvelopeUserId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($budgetEnvelopeUserId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeRenamedDomainEvent_v1(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeName,
            ),
        );
    }

    public function credit(
        BudgetEnvelopeCreditMoney $budgetEnvelopeCreditMoney,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        UserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeCreditedDomainEvent_v1(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeCreditMoney,
                (string) $budgetEnvelopeEntryDescription,
            ),
        );
    }

    public function debit(
        BudgetEnvelopeDebitMoney $budgetEnvelopeDebitMoney,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        UserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeDebitedDomainEvent_v1(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeDebitMoney,
                (string) $budgetEnvelopeEntryDescription,
            ),
        );
    }

    public function delete(UserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeDeletedDomainEvent_v1(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                true,
            ),
        );
    }

    public function updateTargetedAmount(
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        UserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeTargetedAmountChangedDomainEvent_v1(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeTargetedAmount,
            ),
        );
    }

    public function changeCurrency(BudgetEnvelopeCurrency $budgetEnvelopeCurrency, UserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeCurrencyChangedDomainEvent_v1(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeCurrency,
            ),
        );
    }

    public function rewind(UserId $userId, \DateTimeImmutable $desiredDateTime): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeRewoundDomainEvent_v1(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $this->budgetEnvelopeName,
                (string) $this->budgetEnvelopeTargetedAmount,
                (string) $this->budgetEnvelopeCurrentAmount,
                (string) $this->budgetEnvelopeCurrency,
                UtcClock::fromDateTimeToString($this->updatedAt),
                UtcClock::fromImmutableToString($desiredDateTime),
                $this->isDeleted,
            )
        );
    }

    public function replay(UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeReplayedDomainEvent_v1(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $this->budgetEnvelopeName,
                (string) $this->budgetEnvelopeTargetedAmount,
                (string) $this->budgetEnvelopeCurrentAmount,
                (string) $this->budgetEnvelopeCurrency,
                UtcClock::fromDateTimeToString($this->updatedAt),
                $this->isDeleted,
            ),
        );
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function getBudgetEnvelopeName(): BudgetEnvelopeName
    {
        return $this->budgetEnvelopeName;
    }

    public function setAggregateVersion(int $aggregateVersion): self
    {
        $this->aggregateVersion = $aggregateVersion;

        return $this;
    }

    public function getAggregateId(): string
    {
        return (string) $this->budgetEnvelopeId;
    }

    public function applyBudgetEnvelopeAddedDomainEvent_v1(BudgetEnvelopeAddedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeId = BudgetEnvelopeId::fromString($event->aggregateId);
        $this->userId = UserId::fromString($event->userId);
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            '0.00',
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            '0.00',
            $event->targetedAmount,
        );
        $this->budgetEnvelopeCurrency = BudgetEnvelopeCurrency::fromString($event->currency);
        $this->context = Context::from($event->contextId, $event->context);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
        $this->isDeleted = false;
    }

    public function applyBudgetEnvelopeRenamedDomainEvent_v1(BudgetEnvelopeRenamedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeCreditedDomainEvent_v1(BudgetEnvelopeCreditedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            (string) (
                floatval(
                    (string) $this->budgetEnvelopeCurrentAmount
                ) + floatval(
                    $event->creditMoney,
                )
            ),
            (string) $this->budgetEnvelopeTargetedAmount,
        );
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeDebitedDomainEvent_v1(BudgetEnvelopeDebitedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            (string) (
                floatval(
                    (string) $this->budgetEnvelopeCurrentAmount,
                ) - floatval(
                    $event->debitMoney,
                )
            ),
            (string) $this->budgetEnvelopeTargetedAmount,
        );
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeDeletedDomainEvent_v1(BudgetEnvelopeDeletedDomainEvent_v1 $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeTargetedAmountChangedDomainEvent_v1(
        BudgetEnvelopeTargetedAmountChangedDomainEvent_v1 $event,
    ): void {
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeCurrencyChangedDomainEvent_v1(BudgetEnvelopeCurrencyChangedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeCurrency = BudgetEnvelopeCurrency::fromString($event->currency);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeReplayedDomainEvent_v1(BudgetEnvelopeReplayedDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            $event->currentAmount,
            $event->targetedAmount,
        );
        $this->updatedAt = UtcClock::fromDatetime($event->updatedAt);
        $this->isDeleted = $event->isDeleted;
    }

    public function applyBudgetEnvelopeRewoundDomainEvent_v1(BudgetEnvelopeRewoundDomainEvent_v1 $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            $event->currentAmount,
            $event->targetedAmount,
        );
        $this->updatedAt = UtcClock::fromDatetime($event->updatedAt);
        $this->isDeleted = $event->isDeleted;
    }

    private function assertOwnership(UserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw BudgetEnvelopeIsNotOwnedByUserException::isNotOwnedByUser();
        }
    }

    private function assertNotDeleted(): void
    {
        if ($this->isDeleted) {
            throw InvalidBudgetEnvelopeOperationException::operationOnDeletedEnvelope();
        }
    }

    public function createSnapshot(): array
    {
        return [
            'budgetEnvelopeId' => (string) $this->budgetEnvelopeId,
            'userId' => (string) $this->userId,
            'budgetEnvelopeName' => (string) $this->budgetEnvelopeName,
            'budgetEnvelopeTargetedAmount' => (string) $this->budgetEnvelopeTargetedAmount,
            'budgetEnvelopeCurrentAmount' => (string) $this->budgetEnvelopeCurrentAmount,
            'budgetEnvelopeCurrency' => (string) $this->budgetEnvelopeCurrency,
            'context' => [
                'contextId' => $this->context->getContextId(),
                'context' => $this->context->getContext(),
            ],
            'updatedAt' => $this->updatedAt->format(\DateTime::ATOM),
            'isDeleted' => $this->isDeleted,
        ];
    }
    
    public static function fromSnapshot(array $data, int $version): self
    {
        $budgetEnvelope = new self();
        
        $budgetEnvelope->budgetEnvelopeId = BudgetEnvelopeId::fromString($data['budgetEnvelopeId']);
        $budgetEnvelope->userId = UserId::fromString($data['userId']);
        $budgetEnvelope->budgetEnvelopeName = BudgetEnvelopeName::fromString($data['budgetEnvelopeName']);
        $budgetEnvelope->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $data['budgetEnvelopeTargetedAmount'],
            $data['budgetEnvelopeCurrentAmount']
        );
        $budgetEnvelope->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            $data['budgetEnvelopeCurrentAmount'],
            $data['budgetEnvelopeTargetedAmount']
        );
        $budgetEnvelope->budgetEnvelopeCurrency = BudgetEnvelopeCurrency::fromString($data['budgetEnvelopeCurrency']);
        $budgetEnvelope->context = Context::from($data['context']['contextId'], $data['context']['context']);
        $budgetEnvelope->updatedAt = new \DateTime($data['updatedAt']);
        $budgetEnvelope->isDeleted = $data['isDeleted'];
        $budgetEnvelope->aggregateVersion = $version;
        
        return $budgetEnvelope;
    }
}
