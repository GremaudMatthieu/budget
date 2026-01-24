<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Domain\NeedsCategories;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/needs-categories', name: 'app_budget_plans_needs_categories_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListNeedsCategoriesController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $locale = $user->languagePreference;

        $categories = array_map(fn (NeedsCategories $category) => [
            'id' => $category->value,
            'name' => $this->translator->trans('needs.'.$category->value, [], 'messages', $locale),
        ], NeedsCategories::cases());

        return new JsonResponse($categories, Response::HTTP_OK);
    }
}
