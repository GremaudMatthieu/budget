<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Queries;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\SharedContext\Domain\Ports\Inbound\QueryInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;

final readonly class GetABudgetPlanWithItsEntriesQuery implements QueryInterface
{
    private string $budgetPlanId;
    private string $userLanguagePreference;
    private string $budgetPlanUserId;

    public function __construct(
        BudgetPlanId $budgetPlanId,
        UserLanguagePreference $userLanguagePreference,
        UserId $budgetPlanUserId,
    ) {
        $this->budgetPlanId = (string) $budgetPlanId;
        $this->userLanguagePreference = (string) $userLanguagePreference;
        $this->budgetPlanUserId = (string) $budgetPlanUserId;
    }

    public function getBudgetPlanId(): BudgetPlanId
    {
        return BudgetPlanId::fromString($this->budgetPlanId);
    }

    public function getUserLanguagePreference(): string
    {
        return $this->userLanguagePreference;
    }

    public function getBudgetPlanUserId(): UserId
    {
        return UserId::fromString($this->budgetPlanUserId);
    }
}
