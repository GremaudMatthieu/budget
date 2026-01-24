<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\Presentation\BudgetPlan\Queries\ListBudgetPlansCalendar;
use App\SharedContext\Domain\Ports\Outbound\QueryBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans-calendar', name: 'app_budget_plans_calendar_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListBudgetPlansCalendarController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new ListBudgetPlansCalendar(
                    UserId::fromString($user->getUuid()),
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
