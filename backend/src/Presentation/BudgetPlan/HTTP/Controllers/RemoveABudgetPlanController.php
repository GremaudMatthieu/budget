<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlan;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanId;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/{uuid}', name: 'app_budget_plan_remove', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
final readonly class RemoveABudgetPlanController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(new RemoveABudgetPlan(
            BudgetPlanId::fromString($uuid),
            UserId::fromString($user->getUuid()),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
