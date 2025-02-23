<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Aggregates;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanAlreadyExistsException;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncome;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanNeed;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanSaving;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanWant;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\Traits\DomainEventsCapabilityTrait;

final class BudgetPlan
{
    use DomainEventsCapabilityTrait;

    private BudgetPlanId $budgetPlanId;
    private BudgetPlanUserId $userId;
    private array $incomes;
    private array $needs;
    private array $wants;
    private array $savings;
    private \DateTimeImmutable $date;
    private int $aggregateVersion = 0;

    private function __construct()
    {
    }

    public static function fromEvents(\Generator $events, EventClassMapInterface $eventClassMap): self
    {
        $aggregate = new self();

        /** @var array{stream_version: int, event_name: string, payload: string} $event */
        foreach ($events as $event) {
            $aggregate->apply(
            /** @var DomainEventInterface $eventClassMap->getEventPathByClassName($event['event_name']) */
            $eventClassMap->getEventPathByClassName($event['event_name'])::fromArray(
                    json_decode($event['payload'], true)
                )
            );
            $aggregate->aggregateVersion = $event['stream_version'];
        }

        return $aggregate;
    }

    public static function create(
        BudgetPlanId $budgetPlanId,
        \DateTimeImmutable $date,
        array $incomes,
        BudgetPlanUserId $userId,
        BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
        UuidGeneratorInterface $uuidGenerator,
    ): self {
        if ($budgetPlanViewRepository->findOneBy([
            'user_uuid' => (string) $userId,
            'date' => $date->format(\DateTime::ATOM),
        ])) {
            throw new BudgetPlanAlreadyExistsException();
        }

        $budgetPlanGeneratedDomainEvent = new BudgetPlanGeneratedDomainEvent(
            (string) $budgetPlanId,
            $date->format(\DateTimeInterface::ATOM),
            array_map(fn(BudgetPlanIncome $income) => $income->toArray(), $incomes),
            array_map(fn(BudgetPlanNeed $need) => $need->toArray(), self::generateFakeNeeds($incomes, $uuidGenerator)),
            array_map(fn(BudgetPlanWant $want) => $want->toArray(), self::generateFakeWants($incomes, $uuidGenerator)),
            array_map(fn(BudgetPlanSaving $saving) => $saving->toArray(), self::generateFakeSavings($incomes, $uuidGenerator)),
            (string) $userId,
        );

        $aggregate = new self();
        $aggregate->apply($budgetPlanGeneratedDomainEvent);
        $aggregate->raiseDomainEvents($budgetPlanGeneratedDomainEvent);

        return $aggregate;
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->applyBudgetPlanGeneratedDomainEvent($event),
            default => throw new \RuntimeException('Unknown event type'),
        };
    }

    private function applyBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        $this->budgetPlanId = BudgetPlanId::fromString($event->aggregateId);
        $this->userId = BudgetPlanUserId::fromString($event->userId);
        $this->date = new \DateTimeImmutable($event->date);
        $this->incomes = array_map(fn(array $income) => BudgetPlanIncome::fromArray($income), $event->incomes);
        $this->needs = array_map(fn(array $income) => BudgetPlanNeed::fromArray($income), $event->needs);
        $this->wants = array_map(fn(array $income) => BudgetPlanWant::fromArray($income), $event->wants);
        $this->savings = array_map(fn(array $income) => BudgetPlanSaving::fromArray($income), $event->savings);
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    private static function generateFakeNeeds(array $incomes, UuidGeneratorInterface $uuidGenerator): array
    {
        $needsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.50;

        return [
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => 'Rent',
                'amount' => (string) ($needsAmount * 0.40),
            ]),
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => 'Utilities',
                'amount' => (string) ($needsAmount * 0.20),
            ]),
            BudgetPlanNeed::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'needName' => 'Groceries',
                'amount' => (string) ($needsAmount * 0.40),
            ]),
        ];
    }

    private static function generateFakeWants(array $incomes, UuidGeneratorInterface $uuidGenerator): array
    {
        $wantsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.30;

        return [
            BudgetPlanWant::fromArray(
                ['uuid' => $uuidGenerator->generate(),
                    'wantName' => 'Entertainment',
                    'amount' => (string) ($wantsAmount * 0.50),
                ]),
            BudgetPlanWant::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'wantName' => 'Dining Out',
                'amount' => (string) ($wantsAmount * 0.50),
            ]),
        ];
    }

    private static function generateFakeSavings(array $incomes, UuidGeneratorInterface $uuidGenerator): array
    {
        $savingsAmount = array_reduce(
                $incomes,
                fn(float $carry, BudgetPlanIncome $income) => $carry + (float) $income->getAmount(),
                0.00,
            ) * 0.20;

        return [
            BudgetPlanSaving::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'savingName' => 'Emergency Fund',
                'amount' => (string) ($savingsAmount * 0.50),
            ]),
            BudgetPlanSaving::fromArray([
                'uuid' => $uuidGenerator->generate(),
                'savingName' => 'Retirement',
                'amount' => (string) ($savingsAmount * 0.50),
            ]),
        ];
    }
}
