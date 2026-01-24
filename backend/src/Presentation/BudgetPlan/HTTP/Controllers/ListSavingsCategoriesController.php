<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Domain\SavingsCategories;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/savings-categories', name: 'app_budget_plans_savings_categories_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListSavingsCategoriesController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $locale = $user->languagePreference;

        $categories = array_map(fn (SavingsCategories $category) => [
            'id' => $category->value,
            'name' => $this->translator->trans('savings.'.$category->value, [], 'messages', $locale),
        ], SavingsCategories::cases());

        return new JsonResponse($categories, Response::HTTP_OK);
    }
}
