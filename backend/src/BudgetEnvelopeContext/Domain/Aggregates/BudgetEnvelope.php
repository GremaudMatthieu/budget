<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Aggregates;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
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
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\DomainEventsCapabilityTrait;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetEnvelope implements AggregateRootInterface
{
    use DomainEventsCapabilityTrait;

    private BudgetEnvelopeId $budgetEnvelopeId;
    private BudgetEnvelopeUserId $userId;
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
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeName $budgetEnvelopeName,
        BudgetEnvelopeCurrency $budgetEnvelopeCurrency,
        Context $context,
    ): self {
        $aggregate = new self();
        $aggregate->raiseDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
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

    public function rename(BudgetEnvelopeName $budgetEnvelopeName, BudgetEnvelopeUserId $budgetEnvelopeUserId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($budgetEnvelopeUserId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeRenamedDomainEvent(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeName,
            ),
        );
    }

    public function credit(
        BudgetEnvelopeCreditMoney $budgetEnvelopeCreditMoney,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeCreditedDomainEvent(
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
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeDebitedDomainEvent(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeDebitMoney,
                (string) $budgetEnvelopeEntryDescription,
            ),
        );
    }

    public function delete(BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeDeletedDomainEvent(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                true,
            ),
        );
    }

    public function updateTargetedAmount(
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeTargetedAmountChangedDomainEvent(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeTargetedAmount,
            ),
        );
    }

    public function changeCurrency(BudgetEnvelopeCurrency $budgetEnvelopeCurrency, BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeCurrencyChangedDomainEvent(
                (string) $this->budgetEnvelopeId,
                (string) $this->userId,
                (string) $budgetEnvelopeCurrency,
            ),
        );
    }

    public function rewind(BudgetEnvelopeUserId $userId, \DateTimeImmutable $desiredDateTime): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeRewoundDomainEvent(
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

    public function replay(BudgetEnvelopeUserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetEnvelopeReplayedDomainEvent(
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

    public function applyBudgetEnvelopeAddedDomainEvent(BudgetEnvelopeAddedDomainEvent $event): void
    {
        $this->budgetEnvelopeId = BudgetEnvelopeId::fromString($event->aggregateId);
        $this->userId = BudgetEnvelopeUserId::fromString($event->userId);
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

    public function applyBudgetEnvelopeRenamedDomainEvent(BudgetEnvelopeRenamedDomainEvent $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeCreditedDomainEvent(BudgetEnvelopeCreditedDomainEvent $event): void
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

    public function applyBudgetEnvelopeDebitedDomainEvent(BudgetEnvelopeDebitedDomainEvent $event): void
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

    public function applyBudgetEnvelopeDeletedDomainEvent(BudgetEnvelopeDeletedDomainEvent $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeTargetedAmountChangedDomainEvent(
        BudgetEnvelopeTargetedAmountChangedDomainEvent $event,
    ): void {
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeCurrencyChangedDomainEvent(BudgetEnvelopeCurrencyChangedDomainEvent $event): void
    {
        $this->budgetEnvelopeCurrency = BudgetEnvelopeCurrency::fromString($event->currency);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeReplayedDomainEvent(BudgetEnvelopeReplayedDomainEvent $event): void
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

    public function applyBudgetEnvelopeRewoundDomainEvent(BudgetEnvelopeRewoundDomainEvent $event): void
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

    private function assertOwnership(BudgetEnvelopeUserId $userId): void
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
}
