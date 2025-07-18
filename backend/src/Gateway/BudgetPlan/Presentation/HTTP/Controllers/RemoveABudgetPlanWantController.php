<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanWantCommand;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/{budgetPlanUuid}/remove-want/{uuid}', name: 'app_budget_plan_remove_want', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
final readonly class RemoveABudgetPlanWantController
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        string $budgetPlanUuid,
        string $uuid,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(new RemoveABudgetPlanWantCommand(
            BudgetPlanId::fromString($budgetPlanUuid),
            BudgetPlanEntryId::fromString($uuid),
            UserId::fromString($user->getUuid()),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
