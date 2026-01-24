<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\Presentation\BudgetPlan\HTTP\DTOs\GetACalendarWithItsBudgetPlansFinancialRatiosByYearInput;
use App\Presentation\BudgetPlan\Queries\GetACalendarWithItsBudgetPlansFinancialRatiosByYear;
use App\SharedContext\Domain\Ports\Outbound\QueryBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans-yearly-calendar', name: 'app_budget_plans_get_calendar', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class GetACalendarWithItsBudgetPlansFinancialRatiosByYearController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserView $user,
        #[MapQueryString] GetACalendarWithItsBudgetPlansFinancialRatiosByYearInput $getACalendarWithItsBudgetPlansFinancialRatiosByYearInput = new GetACalendarWithItsBudgetPlansFinancialRatiosByYearInput(),
    ): JsonResponse {
        return new JsonResponse(
            $this->queryBus->query(
                new GetACalendarWithItsBudgetPlansFinancialRatiosByYear(
                    UserId::fromString($user->getUuid()),
                    UserLanguagePreference::fromString($user->languagePreference),
                    $getACalendarWithItsBudgetPlansFinancialRatiosByYearInput->year ?
                        \DateTimeImmutable::createFromFormat('Y', $getACalendarWithItsBudgetPlansFinancialRatiosByYearInput->year) :
                        new \DateTimeImmutable('now'),
                ),
            ),
            Response::HTTP_OK,
        );
    }
}
