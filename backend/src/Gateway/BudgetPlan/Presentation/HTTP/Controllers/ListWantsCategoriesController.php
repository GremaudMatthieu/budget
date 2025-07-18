<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Domain\Enums\WantsCategoriesEnum;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/wants-categories', name: 'app_budget_plans_wants_categories_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListWantsCategoriesController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $locale = $user->languagePreference;

        $categories = array_map(fn (WantsCategoriesEnum $category) => [
            'id' => $category->value,
            'name' => $this->translator->trans('wants.'.$category->value, [], 'messages', $locale),
        ], WantsCategoriesEnum::cases());

        return new JsonResponse($categories, Response::HTTP_OK);
    }
}
