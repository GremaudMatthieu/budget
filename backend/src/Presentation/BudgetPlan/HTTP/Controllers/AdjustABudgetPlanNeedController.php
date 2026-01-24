<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\AdjustABudgetPlanNeed;
use App\BudgetPlanContext\Domain\BudgetPlanEntryAmount;
use App\BudgetPlanContext\Domain\BudgetPlanEntryName;
use App\BudgetPlanContext\Domain\BudgetPlanNeedCategory;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanId;
use App\Presentation\BudgetPlan\HTTP\DTOs\AdjustABudgetPlanNeedInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/{budgetPlanUuid}/adjust-need/{uuid}', name: 'app_budget_plan_adjust_need', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class AdjustABudgetPlanNeedController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] AdjustABudgetPlanNeedInput $adjustABudgetPlanNeedInput,
        string $budgetPlanUuid,
        string $uuid,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new AdjustABudgetPlanNeed(
                BudgetPlanId::fromString($budgetPlanUuid),
                BudgetPlanEntryId::fromString($uuid),
                BudgetPlanEntryName::fromString($adjustABudgetPlanNeedInput->name),
                BudgetPlanEntryAmount::fromString($adjustABudgetPlanNeedInput->amount),
                BudgetPlanNeedCategory::fromString($adjustABudgetPlanNeedInput->category),
                UserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
