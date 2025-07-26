<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Enums;

enum WantsCategoriesEnum: string
{
    case ENTERTAINMENT = 'entertainment';
    case DINING_OUT = 'dining-out';
    case SHOPPING = 'shopping';
    case SUBSCRIPTIONS = 'subscriptions';
    case LUXURY_ITEMS = 'luxury-items';
    case HOBBIES = 'hobbies';
}
