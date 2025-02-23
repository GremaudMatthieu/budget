<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlansPaginatedInterface
{
    public function jsonSerialize(): array;
}
