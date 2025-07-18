<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Enums;

enum NeedsCategoriesEnum: string
{
    case RENT = 'rent';
    case MORTGAGE = 'mortgage';
    case UTILITIES = 'utilities';
    case INSURANCE = 'insurance';
    case FOOD = 'food';
    case TRANSPORTATION = 'transportation';
    case HEALTHCARE = 'healthcare';
    case DEBT_REPAYMENT = 'debt-repayment';
}
