<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Services;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanCategoriesTranslatorInterface;
use App\SharedContext\Domain\Ports\Outbound\TranslatorInterface;

final readonly class BudgetPlanCategoriesTranslator implements BudgetPlanCategoriesTranslatorInterface
{
    public const array CATEGORIES = [
        'incomeCategoriesRatio' => 'incomes',
        'incomeCategoriesTotal' => 'incomes',
        'needCategoriesRatio' => 'needs',
        'needCategoriesTotal' => 'needs',
        'savingCategoriesRatio' => 'savings',
        'savingCategoriesTotal' => 'savings',
        'wantCategoriesRatio' => 'wants',
        'wantCategoriesTotal' => 'wants',
    ];

    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function translate(array $budgetPlans, string $locale): array
    {
        $domains = self::CATEGORIES;

        return array_reduce(
            array_keys(
                $domains,
            ),
            function ($translatedBudgetPlans, $key) use ($budgetPlans, $domains, $locale) {
                if (isset($budgetPlans[$key])) {
                    $translatedBudgetPlans[$key] = $this->translateCategories(
                        $budgetPlans[$key],
                        $domains[$key],
                        $locale,
                    );
                }

                return $translatedBudgetPlans;
            },
            $budgetPlans
        );
    }

    private function translateCategories(array $categories, string $domain, string $locale): array
    {
        return array_reduce(
            array_keys($categories),
            function ($translatedCategories, $key) use ($categories, $domain, $locale) {
                $translatedKey = $this->translator->trans($domain.'.'.$key, [], 'messages', $locale);
                $translatedCategories[$translatedKey] = $categories[$key];

                return $translatedCategories;
            },
            []
        );
    }
}
