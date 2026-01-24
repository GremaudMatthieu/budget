<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\HTTP\Controllers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanSaving;
use App\BudgetPlanContext\Domain\BudgetPlanEntryAmount;
use App\BudgetPlanContext\Domain\BudgetPlanEntryName;
use App\BudgetPlanContext\Domain\BudgetPlanSavingCategory;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanEntryId;
use App\BudgetPlanContext\Domain\Identifiers\BudgetPlanId;
use App\Presentation\BudgetPlan\HTTP\DTOs\AddABudgetPlanSavingInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/budget-plans/{uuid}/add-saving', name: 'app_budget_plan_add_saving', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class AddABudgetPlanSavingController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] AddABudgetPlanSavingInput $addABudgetPlanSavingInput,
        string $uuid,
        #[CurrentUser] UserView $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new AddABudgetPlanSaving(
                BudgetPlanId::fromString($uuid),
                BudgetPlanEntryId::fromString($addABudgetPlanSavingInput->uuid),
                BudgetPlanEntryName::fromString($addABudgetPlanSavingInput->name),
                BudgetPlanEntryAmount::fromString($addABudgetPlanSavingInput->amount),
                BudgetPlanSavingCategory::fromString($addABudgetPlanSavingInput->category),
                UserId::fromString($user->getUuid()),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
