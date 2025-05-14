<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Aggregates;

use App\BudgetPlanContext\Domain\Events\BudgetPlanCurrencyChangedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanIsNotOwnedByUserException;
use App\BudgetPlanContext\Domain\Exceptions\InvalidBudgetPlanOperationException;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanCurrency;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryAmount;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryName;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncome;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncomeCategory;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanNeed;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanNeedCategory;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanSaving;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanSavingCategory;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanWant;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanWantCategory;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\DomainEventsCapabilityTrait;
use App\SharedContext\Domain\Ports\Outbound\TranslatorInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetPlan implements AggregateRootInterface
{
    use DomainEventsCapabilityTrait;

    private BudgetPlanId $budgetPlanId;
    private BudgetPlanUserId $userId;
    private BudgetPlanCurrency $currency;
    private array $incomes;
    private array $needs;
    private array $wants;
    private array $savings;
    private(set) \DateTimeImmutable $date;
    private int $aggregateVersion = 0;
    private bool $isDeleted = false;
    private \DateTime $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        BudgetPlanId $budgetPlanId,
        \DateTimeImmutable $date,
        array $incomes,
        BudgetPlanUserId $userId,
        UserLanguagePreference $userLanguagePreference,
        BudgetPlanCurrency $currency,
        Context $context,
        UuidGeneratorInterface $uuidGenerator,
        TranslatorInterface $translator,
    ): self {
        $aggregate = new self();
        $aggregate->raiseDomainEvent(
            new BudgetPlanGeneratedDomainEvent(
                (string) $budgetPlanId,
                UtcClock::fromImmutableToString($date),
                (string) $currency,
                array_map(fn(BudgetPlanIncome $income) => $income->toArray(), $incomes),
                array_map(fn(BudgetPlanNeed $need) => $need->toArray(), self::generateFakeNeeds($incomes, (string) $userLanguagePreference, $uuidGenerator, $translator)),
                array_map(fn(BudgetPlanWant $want) => $want->toArray(), self::generateFakeWants($incomes, (string) $userLanguagePreference, $uuidGenerator, $translator)),
                array_map(fn(BudgetPlanSaving $saving) => $saving->toArray(), self::generateFakeSavings($incomes, (string) $userLanguagePreference, $uuidGenerator, $translator)),
                (string) $userId,
                $context->getContextId(),
                $context->getContext(),
            ),
        );

        return $aggregate;
    }

    public static function createWithOneThatAlreadyExists(
        BudgetPlanId $budgetPlanId,
        \DateTimeImmutable $date,
        BudgetPlanUserId $userId,
        BudgetPlan $budgetPlanToCopy,
        Context $context,
        UuidGeneratorInterface $uuidGenerator,
    ): self {
        $aggregate = new self();
        $currentDate = UtcClock::immutableNow();
        $aggregate->raiseDomainEvent(
            new BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                (string) $budgetPlanId,
                UtcClock::fromImmutableToString($date),
                (string) $budgetPlanToCopy->currency,
                array_map(fn(BudgetPlanIncome $income) => $income->toArray(),
                    self::generateIncomesFromABudgetPlanThatAlreadyExists(
                        $budgetPlanToCopy->incomes,
                        $uuidGenerator,
                        $currentDate,
                    ),
                ),
                array_map(fn(BudgetPlanNeed $need) => $need->toArray(),
                    self::generateNeedsFromABudgetPlanThatAlreadyExists(
                        $budgetPlanToCopy->needs,
                        $uuidGenerator,
                        $currentDate,
                    ),
                ),
                array_map(fn(BudgetPlanWant $want) => $want->toArray(),
                    self::generateWantsFromABudgetPlanThatAlreadyExists(
                        $budgetPlanToCopy->wants,
                        $uuidGenerator,
                        $currentDate,
                    ),
                ),
                array_map(fn(BudgetPlanSaving $saving) => $saving->toArray(),
                    self::generateSavingsFromABudgetPlanThatAlreadyExists(
                        $budgetPlanToCopy->savings,
                        $uuidGenerator,
                        $currentDate,
                    ),
                ),
                (string) $userId,
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

    public function changeCurrency(
        BudgetPlanCurrency $budgetPlanCurrency,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanCurrencyChangedDomainEvent(
                (string) $this->budgetPlanId,
                (string) $this->userId,
                (string) $budgetPlanCurrency,
            ),
        );
    }

    public function addIncome(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $incomeId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanIncomeCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanIncomeAddedDomainEvent(
                (string) $budgetPlanId,
                (string) $incomeId,
                (string) $userId,
                (string) $amount,
                (string) $name,
                (string) $category,
            )
        );
    }

    public function addWant(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $wantId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanWantCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanWantAddedDomainEvent(
                (string) $budgetPlanId,
                (string) $wantId,
                (string) $userId,
                (string) $amount,
                (string) $name,
                (string) $category,
            ),
        );
    }

    public function addNeed(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $needId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanNeedCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanNeedAddedDomainEvent(
                (string) $budgetPlanId,
                (string) $needId,
                (string) $userId,
                (string) $amount,
                (string) $name,
                (string) $category,
            ),
        );
    }

    public function addSaving(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $savingId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanSavingCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanSavingAddedDomainEvent(
                (string) $budgetPlanId,
                (string) $savingId,
                (string) $userId,
                (string) $amount,
                (string) $name,
                (string) $category,
            ),
        );
    }

    public function adjustAWant(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $wantId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanWantCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanWantAdjustedDomainEvent(
                (string) $budgetPlanId,
                (string) $wantId,
                (string) $userId,
                (string) $amount,
                (string) $name,
                (string) $category,
            ),
        );
    }

    public function adjustANeed(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $needId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanNeedCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanNeedAdjustedDomainEvent(
                (string) $budgetPlanId,
                (string) $needId,
                (string) $userId,
                (string) $amount,
                (string) $name,
                (string) $category,
            ),
        );
    }

    public function adjustASaving(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $savingId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanSavingCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanSavingAdjustedDomainEvent(
                (string) $budgetPlanId,
                (string) $savingId,
                (string) $userId,
                (string) $amount,
                (string) $name,
                (string) $category,
            ),
        );
    }

    public function adjustAnIncome(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $incomeId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanIncomeCategory $category,
        BudgetPlanUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanIncomeAdjustedDomainEvent(
                (string) $budgetPlanId,
                (string) $incomeId,
                (string) $userId,
                (string) $amount,
                (string) $name,
                (string) $category,
            )
        );
    }

    public function removeAnIncome(BudgetPlanEntryId $incomeId, BudgetPlanUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanIncomeRemovedDomainEvent((string) $this->budgetPlanId, (string) $incomeId, (string) $userId),
        );
    }

    public function removeASaving(BudgetPlanEntryId $savingId, BudgetPlanUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanSavingRemovedDomainEvent(
                (string) $this->budgetPlanId,
                (string) $savingId,
                (string) $userId,
            ),
        );
    }

    public function removeAWant(BudgetPlanEntryId $wantId, BudgetPlanUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanWantRemovedDomainEvent(
                (string) $this->budgetPlanId,
                (string) $wantId,
                (string) $userId,
            ),
        );
    }

    public function removeANeed(BudgetPlanEntryId $needId, BudgetPlanUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanNeedRemovedDomainEvent(
                (string) $this->budgetPlanId,
                (string) $needId,
                (string) $userId,
            )
        );
    }

    public function remove(BudgetPlanUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new BudgetPlanRemovedDomainEvent(
                (string) $this->budgetPlanId,
                (string) $this->userId,
                true,
            ),
        );
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function setAggregateVersion(int $aggregateVersion): self
    {
        $this->aggregateVersion = $aggregateVersion;

        return $this;
    }

    public function getAggregateId(): string
    {
        return (string) $this->budgetPlanId;
    }

    public function applyBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        $this->budgetPlanId = BudgetPlanId::fromString($event->aggregateId);
        $this->userId = BudgetPlanUserId::fromString($event->userId);
        $this->date = new \DateTimeImmutable($event->date);
        $this->currency = BudgetPlanCurrency::fromString($event->currency);
        $this->incomes = array_map(fn(array $income) => BudgetPlanIncome::fromArray($income), $event->incomes);
        $this->needs = array_map(fn(array $income) => BudgetPlanNeed::fromArray($income), $event->needs);
        $this->wants = array_map(fn(array $income) => BudgetPlanWant::fromArray($income), $event->wants);
        $this->savings = array_map(fn(array $income) => BudgetPlanSaving::fromArray($income), $event->savings);
        $this->isDeleted = false;
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event
    ): void {
        $this->budgetPlanId = BudgetPlanId::fromString($event->aggregateId);
        $this->userId = BudgetPlanUserId::fromString($event->userId);
        $this->date = new \DateTimeImmutable($event->date);
        $this->currency = BudgetPlanCurrency::fromString($event->currency);
        $this->incomes = array_map(fn(array $income) => BudgetPlanIncome::fromArray($income), $event->incomes);
        $this->needs = array_map(fn(array $income) => BudgetPlanNeed::fromArray($income), $event->needs);
        $this->wants = array_map(fn(array $income) => BudgetPlanWant::fromArray($income), $event->wants);
        $this->savings = array_map(fn(array $income) => BudgetPlanSaving::fromArray($income), $event->savings);
        $this->isDeleted = false;
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanCurrencyChangedDomainEvent(BudgetPlanCurrencyChangedDomainEvent $event): void
    {
        $this->currency = BudgetPlanCurrency::fromString($event->currency);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanRemovedDomainEvent(BudgetPlanRemovedDomainEvent $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanIncomeAddedDomainEvent(BudgetPlanIncomeAddedDomainEvent $event): void
    {
        $this->incomes[] = BudgetPlanIncome::fromArray([
            'uuid' => $event->uuid,
            'incomeName' => $event->name,
            'category' => $event->category,
            'amount' => $event->amount,
        ]);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanWantAddedDomainEvent(BudgetPlanWantAddedDomainEvent $event): void
    {
        $this->wants[] = BudgetPlanWant::fromArray([
            'uuid' => $event->uuid,
            'wantName' => $event->name,
            'category' => $event->category,
            'amount' => $event->amount,
        ]);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanNeedAddedDomainEvent(BudgetPlanNeedAddedDomainEvent $event): void
    {
        $this->needs[] = BudgetPlanNeed::fromArray([
            'uuid' => $event->uuid,
            'needName' => $event->name,
            'category' => $event->category,
            'amount' => $event->amount,
        ]);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanSavingAddedDomainEvent(BudgetPlanSavingAddedDomainEvent $event): void
    {
        $this->savings[] = BudgetPlanSaving::fromArray([
            'uuid' => $event->uuid,
            'savingName' => $event->name,
            'category' => $event->category,
            'amount' => $event->amount,
        ]);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanIncomeAdjustedDomainEvent(BudgetPlanIncomeAdjustedDomainEvent $event): void
    {
        $this->incomes = array_map(function(BudgetPlanIncome $income) use ($event) {
            if ($income->getUuid() === $event->uuid) {
                return BudgetPlanIncome::fromArray([
                    'uuid' => $event->uuid,
                    'incomeName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]);
            }
            return $income;
        }, $this->incomes);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanWantAdjustedDomainEvent(BudgetPlanWantAdjustedDomainEvent $event): void
    {
        $this->wants = array_map(function(BudgetPlanWant $want) use ($event) {
            if ($want->getUuid() === $event->uuid) {
                return BudgetPlanWant::fromArray([
                    'uuid' => $event->uuid,
                    'wantName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]);
            }
            return $want;
        }, $this->wants);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanNeedAdjustedDomainEvent(BudgetPlanNeedAdjustedDomainEvent $event): void
    {
        $this->needs = array_map(function(BudgetPlanNeed $need) use ($event) {
            if ($need->getUuid() === $event->uuid) {
                return BudgetPlanNeed::fromArray([
                    'uuid' => $event->uuid,
                    'needName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]);
            }
            return $need;
        }, $this->needs);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanSavingAdjustedDomainEvent(BudgetPlanSavingAdjustedDomainEvent $event): void
    {
        $this->savings = array_map(function(BudgetPlanSaving $saving) use ($event) {
            if ($saving->getUuid() === $event->uuid) {
                return BudgetPlanSaving::fromArray([
                    'uuid' => $event->uuid,
                    'savingName' => $event->name,
                    'category' => $event->category,
                    'amount' => $event->amount,
                ]);
            }
            return $saving;
        }, $this->savings);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanIncomeRemovedDomainEvent(BudgetPlanIncomeRemovedDomainEvent $event): void
    {
        $this->incomes = array_filter($this->incomes, fn(BudgetPlanIncome $income) => $income->getUuid() !== $event->uuid);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanWantRemovedDomainEvent(BudgetPlanWantRemovedDomainEvent $event): void
    {
        $this->wants = array_filter($this->wants, fn(BudgetPlanWant $want) => $want->getUuid() !== $event->uuid);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanNeedRemovedDomainEvent(BudgetPlanNeedRemovedDomainEvent $event): void
    {
        $this->needs = array_filter($this->needs, fn(BudgetPlanNeed $need) => $need->getUuid() !== $event->uuid);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetPlanSavingRemovedDomainEvent(BudgetPlanSavingRemovedDomainEvent $event): void
    {
        $this->savings = array_filter($this->savings, fn(BudgetPlanSaving $saving) => $saving->getUuid() !== $event->uuid);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    private static function generateFakeNeeds(
        array $incomes,
        string $userPreferredLanguage,
        UuidGeneratorInterface $uuidGenerator,
        TranslatorInterface $translator
    ): array {
        $needsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.50;

        return [
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => $translator->trans(
                    id:'needs.rent',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'rent',
                'amount' => \sprintf("%.2f", $needsAmount * 0.40),
            ]),
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => $translator->trans(
                    id:'needs.utilities',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'utilities',
                'amount' => \sprintf("%.2f", $needsAmount * 0.20),
            ]),
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => $translator->trans(
                    id:'needs.food',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'food',
                'amount' => \sprintf("%.2f", $needsAmount * 0.40),
            ]),
        ];
    }

    private static function generateFakeWants(
        array $incomes,
        string $userPreferredLanguage,
        UuidGeneratorInterface $uuidGenerator,
        TranslatorInterface $translator,
    ): array {
        $wantsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.30;

        return [
            BudgetPlanWant::fromArray(
                ['uuid' => $uuidGenerator->generate(),
                    'wantName' => $translator->trans(
                        id:'wants.entertainment',
                        domain: 'messages',
                        locale: $userPreferredLanguage,
                    ),
                    'category' => 'entertainment',
                    'amount' => \sprintf("%.2f", $wantsAmount * 0.50),
                ]),
            BudgetPlanWant::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'wantName' => $translator->trans(
                    id:'wants.dining-out',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'dining-out',
                'amount' => \sprintf("%.2f", $wantsAmount * 0.50),
            ]),
        ];
    }

    private static function generateFakeSavings(
        array $incomes,
        string $userPreferredLanguage,
        UuidGeneratorInterface $uuidGenerator,
        TranslatorInterface $translator,
    ): array {
        $savingsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.20;

        return [
            BudgetPlanSaving::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'savingName' => $translator->trans(
                    id:'savings.emergency-fund',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'emergency-fund',
                'amount' => \sprintf("%.2f", $savingsAmount * 0.50),
            ]),
            BudgetPlanSaving::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'savingName' => $translator->trans(
                    id:'savings.retirement',
                    domain: 'messages',
                    locale: $userPreferredLanguage,
                ),
                'category' => 'retirement',
                'amount' => \sprintf("%.2f", $savingsAmount * 0.50),
            ]),
        ];
    }

    private static function generateIncomesFromABudgetPlanThatAlreadyExists(
        array $existingIncomes,
        UuidGeneratorInterface $uuidGenerator,
        \DateTimeImmutable $currentDate,
    ): array {
        return array_map(function(BudgetPlanIncome $income) use ($uuidGenerator, $currentDate) {
            $newIncome = [];
            $newIncome['uuid'] = $uuidGenerator->generate();
            $newIncome['amount'] = $income->getAmount();
            $newIncome['category'] = $income->getCategory();
            $newIncome['incomeName'] = $income->getIncomeName();

            return BudgetPlanIncome::fromArray($newIncome);
        }, $existingIncomes);
    }

    private static function generateNeedsFromABudgetPlanThatAlreadyExists(
        array $existingNeeds,
        UuidGeneratorInterface $uuidGenerator,
        \DateTimeImmutable $currentDate,
    ): array {
        return array_map(function(BudgetPlanNeed $need) use ($uuidGenerator, $currentDate) {
            $newNeed = [];
            $newNeed['uuid'] = $uuidGenerator->generate();
            $newNeed['amount'] = $need->getAmount();
            $newNeed['category'] = $need->getCategory();
            $newNeed['needName'] = $need->getNeedName();

            return BudgetPlanNeed::fromArray($newNeed);
        }, $existingNeeds);
    }

    private static function generateSavingsFromABudgetPlanThatAlreadyExists(
        array $existingSavings,
        UuidGeneratorInterface $uuidGenerator,
        \DateTimeImmutable $currentDate,
    ): array {
        return array_map(function(BudgetPlanSaving $saving) use ($uuidGenerator, $currentDate) {
            $newSaving = [];
            $newSaving['uuid'] = $uuidGenerator->generate();
            $newSaving['amount'] = $saving->getAmount();
            $newSaving['category'] = $saving->getCategory();
            $newSaving['savingName'] = $saving->getSavingName();

            return BudgetPlanSaving::fromArray($newSaving);
        }, $existingSavings);
    }

    private static function generateWantsFromABudgetPlanThatAlreadyExists(
        array $existingWants,
        UuidGeneratorInterface $uuidGenerator,
        \DateTimeImmutable $currentDate,
    ): array {
        return array_map(function(BudgetPlanWant $want) use ($uuidGenerator, $currentDate) {
            $newWant = [];
            $newWant['uuid'] = $uuidGenerator->generate();
            $newWant['amount'] = $want->getAmount();
            $newWant['category'] = $want->getCategory();
            $newWant['wantName'] = $want->getWantName();

            return BudgetPlanWant::fromArray($newWant);
        }, $existingWants);
    }

    private function assertOwnership(BudgetPlanUserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw BudgetPlanIsNotOwnedByUserException::isNotOwnedByUser();
        }
    }

    private function assertNotDeleted(): void
    {
        if ($this->isDeleted) {
            throw InvalidBudgetPlanOperationException::operationOnDeletedEnvelope();
        }
    }
}
