<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Queries;

use App\SharedContext\Domain\Ports\Inbound\QueryInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;

final readonly class GetACalendarWithItsBudgetPlansFinancialRatiosByYearQuery implements QueryInterface
{
    private string $budgetPlanUserId;
    private string $userLanguagePreference;
    private string $date;

    public function __construct(
        UserId $budgetPlanUserId,
        UserLanguagePreference $userLanguagePreference,
        \DateTimeImmutable $date,
    ) {
        $this->budgetPlanUserId = (string) $budgetPlanUserId;
        $this->userLanguagePreference = (string) $userLanguagePreference;
        $this->date = $date->format('Y');
    }

    public function getBudgetPlanUserId(): UserId
    {
        return UserId::fromString($this->budgetPlanUserId);
    }

    public function getUserLanguagePreference(): string
    {
        return $this->userLanguagePreference;
    }

    public function getDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('Y', $this->date);
    }
}
