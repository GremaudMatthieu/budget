<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanIncomeCommand;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryAmount;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanEntryName;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanIncomeCategory;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\Gateway\BudgetPlan\Presentation\HTTP\DTOs\AddABudgetPlanIncomeInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/{uuid}/add-income', name: 'app_budget_plan_add_income', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class AddABudgetPlanIncomeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] AddABudgetPlanIncomeInput $addABudgetPlanIncomeInput,
        string $uuid,
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new AddABudgetPlanIncomeCommand(
                BudgetPlanId::fromString($uuid),
                BudgetPlanEntryId::fromString($addABudgetPlanIncomeInput->uuid),
                BudgetPlanEntryName::fromString($addABudgetPlanIncomeInput->name),
                BudgetPlanEntryAmount::fromString($addABudgetPlanIncomeInput->amount),
                BudgetPlanIncomeCategory::fromString($addABudgetPlanIncomeInput->category),
                BudgetPlanUserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
