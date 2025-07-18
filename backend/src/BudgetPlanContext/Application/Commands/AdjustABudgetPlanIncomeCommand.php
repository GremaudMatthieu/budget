<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryAmount;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryName;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncomeCategory;
use App\SharedContext\Domain\Ports\Inbound\CommandInterface;
use App\SharedContext\Domain\ValueObjects\UserId;

final readonly class AdjustABudgetPlanIncomeCommand implements CommandInterface
{
    private string $budgetPlanId;
    private string $entryId;
    private string $amount;
    private string $category;
    private string $name;
    private string $userId;

    public function __construct(
        BudgetPlanId $budgetPlanId,
        BudgetPlanEntryId $entryId,
        BudgetPlanEntryName $name,
        BudgetPlanEntryAmount $amount,
        BudgetPlanIncomeCategory $category,
        UserId $userId,
    ) {
        $this->budgetPlanId = (string) $budgetPlanId;
        $this->entryId = (string) $entryId;
        $this->name = (string) $name;
        $this->amount = (string) $amount;
        $this->category = (string) $category;
        $this->userId = (string) $userId;
    }

    public function getBudgetPlanId(): BudgetPlanId
    {
        return BudgetPlanId::fromString($this->budgetPlanId);
    }

    public function getEntryId(): BudgetPlanEntryId
    {
        return BudgetPlanEntryId::fromString($this->entryId);
    }

    public function getAmount(): BudgetPlanEntryAmount
    {
        return BudgetPlanEntryAmount::fromString($this->amount);
    }

    public function getName(): BudgetPlanEntryName
    {
        return BudgetPlanEntryName::fromString($this->name);
    }

    public function getCategory(): BudgetPlanIncomeCategory
    {
        return BudgetPlanIncomeCategory::fromString($this->category);
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }
}
