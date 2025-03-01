<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanCurrency;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;

final readonly class GenerateABudgetPlanCommand implements CommandInterface
{
    private string $budgetPlanId;
    private \DateTimeImmutable $date;
    private array $incomes;
    private string $userId;
    private string $userLanguagePreference;
    private string $currency;

    public function __construct(
        BudgetPlanId $budgetPlanId,
        \DateTimeImmutable $date,
        array $incomes,
        BudgetPlanUserId $userId,
        UserLanguagePreference $userLanguagePreference,
        BudgetPlanCurrency $currency,
    ) {
        $this->budgetPlanId = (string) $budgetPlanId;
        $this->date = $date;
        $this->incomes = $incomes;
        $this->userId = (string) $userId;
        $this->userLanguagePreference = (string) $userLanguagePreference;
        $this->currency = (string) $currency;
    }

    public function getBudgetPlanId(): BudgetPlanId
    {
        return BudgetPlanId::fromString($this->budgetPlanId);
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getIncomes(): array
    {
        return $this->incomes;
    }

    public function getUserLanguagePreference(): UserLanguagePreference
    {
        return UserLanguagePreference::fromString($this->userLanguagePreference);
    }

    public function getUserId(): BudgetPlanUserId
    {
        return BudgetPlanUserId::fromString($this->userId);
    }

    public function getCurrency(): BudgetPlanCurrency
    {
        return BudgetPlanCurrency::fromString($this->currency);
    }
}
