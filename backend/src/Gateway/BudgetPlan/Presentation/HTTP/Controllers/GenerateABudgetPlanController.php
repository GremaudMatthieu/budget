<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\GenerateABudgetPlanCommand;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncome;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\Gateway\BudgetPlan\Presentation\HTTP\DTOs\GenerateABudgetPlanInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/generate', name: 'app_budget_plan_generate', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class GenerateABudgetPlanController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] GenerateABudgetPlanInput $generateABudgetPlanInput,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new GenerateABudgetPlanCommand(
                BudgetPlanId::fromString($generateABudgetPlanInput->uuid),
                $generateABudgetPlanInput->date,
                array_map(
                    fn($income) => BudgetPlanIncome::fromArray($income),
                    $generateABudgetPlanInput->incomes
                ),
                BudgetPlanUserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
