<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Enums;

enum IncomesCategoriesEnum: string
{
    case SALARY = 'salary';
    case FREELANCE = 'freelance';
    case BUSINESS = 'business';
    case RENTAL_INCOME = 'rental-income';
    case INVESTMENTS = 'investments';
    case GOVERNMENT_BENEFITS = 'government-benefits';
    case PENSION = 'pension';
    case SIDE_HUSTLE = 'side-hustle';
    case CHILD_SUPPORT = 'child-support';
    case OTHER = 'other';
}
