<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\ChangeABudgetPlanCurrencyCommand;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanCurrency;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\Gateway\BudgetPlan\Presentation\HTTP\DTOs\ChangeABudgetPlanCurrencyInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
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
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] ChangeABudgetPlanCurrencyInput $changeABudgetPlanCurrencyInput,
        string $uuid,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeABudgetPlanCurrencyCommand(
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
