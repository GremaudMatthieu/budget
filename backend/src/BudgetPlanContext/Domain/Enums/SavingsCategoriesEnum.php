<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Enums;

enum SavingsCategoriesEnum: string
{
    case EMERGENCY_FUND = 'emergency-fund';
    case RETIREMENT = 'retirement';
    case VACATION = 'vacation';
    case EDUCATION = 'education';
    case HOME_PURCHASE = 'home-purchase';
    case INVESTMENT = 'investment';
    case HEALTHCARE = 'healthcare';
    case DEBT_REPAYMENT = 'debt-repayment';
    case CHARITABLE_GIVING = 'charitable-giving';
}
