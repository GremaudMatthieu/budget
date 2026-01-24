<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\ChangeABudgetPlanCurrency;
use App\BudgetPlanContext\Domain\BudgetPlanCurrency;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanId;
use App\Presentation\BudgetPlan\HTTP\DTOs\ChangeABudgetPlanCurrencyInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/{uuid}/change-currency', name: 'app_budget_plan_change_currency', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class ChangeABudgetPlanCurrencyController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] ChangeABudgetPlanCurrencyInput $changeABudgetPlanCurrencyInput,
        string $uuid,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeABudgetPlanCurrency(
                BudgetPlanCurrency::fromString(
                    $changeABudgetPlanCurrencyInput->currency,
                ),
                BudgetPlanId::fromString($uuid),
                UserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
