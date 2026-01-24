<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanId;
use App\Presentation\BudgetPlan\Queries\GetABudgetPlanWithItsEntries;
use App\SharedContext\Domain\Ports\Outbound\QueryBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/{uuid}', name: 'app_budget_plan_get_one_with_its_entries', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class GetABudgetPlanWithItsEntriesController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new GetABudgetPlanWithItsEntries(
                    BudgetPlanId::fromString($uuid),
                    UserLanguagePreference::fromString($user->languagePreference),
                    UserId::fromString($user->getUuid()),
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
