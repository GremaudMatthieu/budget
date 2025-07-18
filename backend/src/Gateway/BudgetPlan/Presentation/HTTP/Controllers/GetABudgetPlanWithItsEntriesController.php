<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Application\Queries\GetABudgetPlanWithItsEntriesQuery;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\SharedContext\Domain\Ports\Outbound\QueryBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
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
        private QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new GetABudgetPlanWithItsEntriesQuery(
                    BudgetPlanId::fromString($uuid),
                    UserLanguagePreference::fromString($user->languagePreference),
                    UserId::fromString($user->getUuid()),
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
