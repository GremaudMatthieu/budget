<?php

namespace App\SharedContext\Domain\Enums;

enum ContextEnum: string
{
    case BUDGET_ENVELOPE = 'BudgetEnvelope';
    case BUDGET_PLAN = 'BudgetPlan';
    case USER = 'User';

    public static function values(): array
    {
        return array_map(fn (self $enum) => $enum->value, self::cases());
    }
}
